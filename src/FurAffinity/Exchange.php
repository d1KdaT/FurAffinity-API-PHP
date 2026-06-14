<?php

/*
 * FurAffinity API PHP
 *
 * @package		FurAffinity-API-PHP
 * @author		d1KdaT <i@d1kdat.me>
 * @license		MIT License
 * @link		https://github.com/d1KdaT/FurAffinity-API-PHP
*/

namespace FurAffinity;

use FurAffinity\Exception\BadRespond;
use FurAffinity\Exception\TimeOut;

class Exchange
{
	/**
	 * The root URL for all FurAffinity requests.
	 */
	public const BASE_URL = 'https://www.furaffinity.net';

	/**
	 * Current version of the FurAffinity API PHP library.
	 * Used for identifying the library in the User-Agent string.
	 */
	private const VERSION = '2.1';

	/**
	 * GitHub repository URL of the library.
	 * Included in the User-Agent for transparency.
	 */
	private const GITHUB = 'https://github.com/d1KdaT/FurAffinity-API-PHP';

	/**
	 * Default User-Agent string used in cURL requests.
	 * Format: FurAffinity-API-PHP/{VERSION} (PHP/{PHP_VERSION}; +{GITHUB})
	 */
	private const DEFAULT_UA = 'FurAffinity-API-PHP/' . self::VERSION . ' (PHP/' . PHP_VERSION . '; +' . self::GITHUB . ')';

	/**
	 * The FurAffinity username used for the current session.
	 */
	private readonly string $username;

	/**
	 * The FurAffinity User-Agent string used in cURL requests
	 */
	private readonly string $user_agent;

	/**
	 * Formatted cookie string for authenticated requests.
	 */
	private readonly string $cookies;

	/**
	 * Optional proxy address in the format "IP:PORT" for cURL requests.
	 */
	private readonly string|null $proxy;

	/**
	 * Optional change of BASE_URL for cURL requests.
	 */
	private readonly string $baseUrl;

	/**
	 * Number of seconds to wait after each request to respect crawling rules.
	 * Defaults to 0 second. Must be updated based on Fur Affinity’s robots.txt on construct.
	 */
	private int $crawl_delay = 0;

	/*
	 * Create an object with the user's cookies: "a", "b" and "__cfduid" (optional) from FurAffinity
	 *
	 * @param array $settings — must contain either:
	 *        ['username', 'b', 'a'] or ['d1', 'd2', 'd3', 'd4'],
	 *        optionally 'proxy' => "IP:PORT" for cURL
	 *
	 * @throw \RuntimeException - cURL is not installed
	 * @throw \InvalidArgumentException - lost one or more of the parameters
	 */
	public function __construct(array $settings)
	{
		if(!function_exists('curl_init'))
		{
			throw new \RuntimeException("cURL is needed, see: https://curl.se/docs/install.html");
		}

		$this->validateSettings($settings);

		$this->username = $settings['username'] ?? $settings['d1'];

		$this->user_agent = $settings['user_agent'] ?? self::DEFAULT_UA;

		$extra_cookies = [];

		foreach (explode(';', $settings['extra_cookies'] ?? '') as $extra_cookie) {
			[$name, $value] = array_map('trim', explode('=', $extra_cookie, 2));
			$extra_cookies[$name] = $value;
		}

		$user_cookies = [
			'__cfduid' => $settings['__cfduid'] ?? $settings['d2'] ?? null,
			'b'        => $settings['b']        ?? $settings['d3'],
			'a'        => $settings['a']        ?? $settings['d4']
		];

		$cookie = array_merge($extra_cookies, $user_cookies);

		$cookie = array_filter($cookie, fn($v) => $v !== null);
		$cookieParts = array_map(
			fn($k, $v) => "$k=$v",
			array_keys($cookie),
			$cookie
		);

		$this->cookies = implode("; ", $cookieParts);

		$this->proxy = $settings['proxy'] ?? null;

		$this->baseUrl = rtrim(($settings['base_url'] ?? '') ?: self::BASE_URL, '/');

		// set delay for requests
		if (isset($settings['crawl_delay']))
		{
			$this->crawl_delay = max(1, (int) ceil($settings['crawl_delay']));
		}
		else
		{
			$this->loadCrawlDelayFromRobots();
		}
	}

	/**
	 * Validates that the settings array contains either:
	 * - ['username', 'b', 'a']
	 *   or
	 * - ['d1', 'd2', 'd3', 'd4']
	 *
	 * @param array $settings Associative array of settings used for authentication
	 *
	 * @throws \InvalidArgumentException If neither set of required keys is present
	 */
	private function validateSettings(array $settings): void
	{
		if (
			!empty(array_diff(['username', 'b', 'a'], array_keys($settings))) &&
			!empty(array_diff(['d1', 'd2', 'd3', 'd4'], array_keys($settings)))
		) {
			throw new \InvalidArgumentException('Missing required authentication keys (username+b+a or d1–d4).');
		}
	}

	/**
	 * Fetches and applies the Crawl-delay directive from robots.txt.
	 * Ensures a minimum delay of 1 second between requests.
	 *
	 * @return void
	 */
	private function loadCrawlDelayFromRobots(): void
	{
		$response = $this->curl($this->baseUrl . '/robots.txt');

		// Match: Crawl-delay: 1 or Crawl-delay: 1.5 etc.
		if ($match = Regex::CrawlDelay->match($response))
		{
			$delay = (float) $match[1];
			$this->crawl_delay = max(1, (int) ceil($delay));
		}
	}

	/**
	 * Fetches basic information about a submission by its ID.
	 *
	 * Parses the page to extract:
	 * - Direct file URL
	 * - Username (from file path)
	 * - Title and display name
	 *
	 * @param int|string $id Submission ID
	 *
	 * @return array{
	 *     file: string,
	 *     username: string,
	 *     title: string,
	 *     display_name: string
	 *     rating: string
	 *     rating_short: string
	 * }|false Returns associative array of data if found, or false on failure
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function getById(int|string $id): array|false
	{
		$return = [];
		$response = $this->curl($this->baseUrl . "/view/$id/");

		if ($match = Regex::DownloadLink->match($response))
		{
			$link = $match[1] ?? '';
			if (str_starts_with($link, '//'))
			{
				$link = 'https:' . $link;
			}

			$return['file'] = $link;
		}

		if (!empty($return['file']) && ($match = Regex::UsernameFromLink->match($return['file'])))
		{
			$return['username'] = $match[1];
		}

		if (!empty($return['username']) && ($match = Regex::TitleAndAuthor->match($response)))
		{
			$return['title'] = $this->decodeHtmlEntitiesDeep($match[1]);
			$return['display_name'] = $this->decodeHtmlEntitiesDeep($match[2]);
		}

		if (!empty($return['display_name']) && ($match = Regex::SubmissionRating->match($response)))
		{
			if(preg_match("/(general|mature|adult)/ui", $match[1] ?? "", $rating_match))
			{
				$submissions_rating = mb_strtolower($rating_match[1] ?? "");
				$return['rating'] = $submissions_rating;
				$return['rating_short'] = mb_substr($submissions_rating, 0, 1);
			}
			else
			{
				$return['rating'] = "unknown";
				$return['rating_short'] = "u";
			}
		}


		if (!empty($return['display_name']))
		{
			if ($match = Regex::SubmissionTags->match_all($response))
			{
				$return['tags'] = array_unique($match[1]);

				$user_tag = $this->usernameToTag($return['username']);

				if (in_array($user_tag, $return['tags']))
				{
					$return['user_tag'] = $user_tag;
				}
			}
			else
			{
				$return['tags'] = [];
			}

			ksort($return, SORT_STRING);
		}

		return $return ?: false;
	}

	/**
	 * Retrieves the watchlist of a given user (users they are watching).
	 *
	 * Iterates through paginated results until the full list is fetched.
	 *
	 * @param string|null $username Target username. If null, defaults to the current session user.
	 *
	 * @return array<int, array{
	 *     username: string,
	 *     display_name: string
	 * }>|false Returns list of users or false if watchlist is empty or failed
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function getWatchlist(?string $username = null): array|false
	{
		$i = 1;
		$return = [];
		$username = $username ?? $this->username;
		$fallback_break = false;

		while(true)
		{
			$response = $this->curl($this->baseUrl . "/watchlist/by/$username/?page=$i");
			$match = Regex::WatchList->match_all($response);

			if (count($return) > 0 && !in_array($return[count($return) - 1]["username"], $match[1]))
			{
				$fallback_break = false;
			}

			if (empty($match[1]) || (count($return) > 0 && in_array($return[count($return) - 1]["username"], $match[1])))
			{
				$fallback_break = true;
			}

			if ($fallback_break)
			{
				break;
			}

			foreach($match[1] as $k => $v)
			{
				$return[] = [
					"username" => $v,
					"user_tag" => $this->usernameToTag($v),
					"display_name" => $match[3][$k] ?? "undefined"
				];
			}

			$i++;
		}

		return $return ?: false;
	}

	/**
	 * Checks whether the given user exists on FurAffinity.
	 *
	 * @param string|null $username Username to check. If null, uses the current session username.
	 *
	 * @return bool True if user exists, false if not
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function checkUserExists(?string $username = null): bool
	{
		$username = $username ?? $this->username;
		$response = $this->curl($this->baseUrl . "/user/$username/");

		return !Regex::UserNotFound->match($response);
	}

	/**
	 * Checks if a given regular expression matches the HTML content of a user's profile page.
	 *
	 * @param string $regex The regular expression to test against the profile HTML.
	 * @param string $username The username whose profile should be fetched.
	 *
	 * @return bool True if the pattern matches the profile page, false otherwise.
	 *
	 * @throws TimeOut        If the request times out.
	 * @throws BadRespond     If the content is invalid or blocked.
	 */
	public function checkRegexOnUserProfile(string $regex, string $username): bool
	{
		$response = $this->curl($this->baseUrl . "/user/$username/");

		return preg_match($regex, $response);
	}

	/**
	 * Verifies whether the current session is authenticated on FurAffinity.
	 *
	 * Checks if the response from the submission page contains a reference to the current username.
	 *
	 * @return bool True if logged in, false otherwise
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function checkLogIn(): bool
	{
		$response = $this->curl($this->baseUrl . "/submit/");
		$match = Regex::CheckLogIn->match($response);

		return (($match[1] ?? '') === $this->username);
	}

	/**
	 * Generate user tag from given username
	 *
	 * @param string $username Username
	 *
	 * @return string user tag
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	private function usernameToTag(string $username): string
	{
		return "u_" . preg_replace(['/\s+/', '/~/', '/\./'], ['_', '_', '_'], urldecode($username));
	}

	/**
	 * Performs a toggle action (e.g., favorite/unfavorite or watch/unwatch) on a target entity.
	 *
	 * The method checks whether the current state allows performing the action,
	 * constructs a signed request using the extracted key, and optionally confirms success via regex pattern.
	 *
	 * @param FavoriteType|WatchType                       $type The type of toggle action (fav/unfav or watch/unwatch)
	 * @param string                                       $initialPath Path used to fetch the target page (e.g., "/view/", "/user/")
	 * @param string|int                                   $target ID or username of the target
	 * @param Regex|null                                   $confirmationPattern Optional pattern to validate successful response
	 *
	 * @return int Result code:
	 *             1 = action performed,
	 *             2 = already in target state,
	 *             3 = target blocked current session user,
	 *             4 = target has been permanently suspended (only for WatchType::Watch),
	 *             0 = failed to perform or detect
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	private function toggleGeneric(
		FavoriteType|WatchType $type,
		string $initialPath,
		string|int $target,
		?Regex $confirmationPattern = null
	): int
	{
		$response = $this->curl($this->baseUrl . $initialPath . $target . "/");

		if ($match = Regex::match_key($response, $type->value, $target))
		{
			$key = $match[1];

			$actionResponse = $this->curl($this->baseUrl . "/{$type->value}/$target/?key=$key");

			if (Regex::TargetSuspended->match($actionResponse))
			{
				return 4;
			}

			if (Regex::TargetBlocked->match($actionResponse))
			{
				return 3;
			}

			if (!$confirmationPattern || $confirmationPattern->match($actionResponse))
			{
				return 1;
			}
		}
		
		if (Regex::match_key($response, $type->inverse()->value, $target))
		{
			return 2;
		}

		return 0;
	}

	/**
	 * Toggles the watch status for the given user.
	 *
	 * @param WatchType            $type Action to perform (watch or unwatch)
	 * @param string               $username Target username
	 *
	 * @return int Result code:
	 *             1 = action performed,
	 *             2 = already in target state,
	 *             3 = target blocked current session user,
	 *             4 = target has been permanently suspended (only for WatchType::Watch),
	 *             0 = failed to perform or detect
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function toggleWatch(WatchType $type, string $username): int
	{
		return $this->toggleGeneric($type, "/user/", $username, Regex::WatchUnWatch);
	}

	/**
	 * Toggles the favorite status of a submission by its ID.
	 *
	 * @param FavoriteType            $type Action to perform (fav or unfav)
	 * @param int|string              $id   Submission ID
	 *
	 * @return int Result code:
	 *             1 = action performed,
	 *             2 = already in target state,
	 *             3 = target blocked current session user,
	 *             0 = failed to perform or detect
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function toggleFavorite(FavoriteType $type, int|string $id): int
	{
		return $this->toggleGeneric($type, "/view/", $id);
	}

	/**
	 * Removes selected submission messages via POST request.
	 *
	 * Uses presence of "New Submissions" block in the response as confirmation.
	 *
	 * @param array $ids Array of submission IDs to remove
	 *
	 * @return bool True if action was successful (response contained expected confirmation), false otherwise
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function removeMsgSubmissions(array $ids = []): bool
	{
		$postdata = [
			"submissions" => $ids,
			"messagecenter-action" => "remove_checked"
		];

		$response = $this->curl($this->baseUrl . "/msg/submissions/", false, http_build_query($postdata));

		return Regex::NewSubmissions->match($response) !== null;
	}

	/**
	 * Retrieves IDs of new submission messages that are greater than the given last ID.
	 *
	 * Scans the "New Submissions" message center page and extracts all submission IDs,
	 * filtering those that are newer than the provided ID.
	 *
	 * @param int|string $last_id The last known submission ID to compare against (default: 0)
	 * @param string     $sort    Type of sort - old/new (default: new)
	 *
	 * @return array<int> Array of sorted submission IDs greater than $last_id,
	 *                         or empty array if no new submissions were found
	 *
	 * @throws TimeOut
	 * @throws BadRespond
	 */
	public function getNewMsgSubmissions(int|string $last_id = 0, string $sort = 'new'): array
	{
		$last_id = (int) $last_id;
		$submissions_ids = [];

		$response = $this->curl($this->baseUrl . "/msg/submissions/$sort@72/");
		$match = Regex::NewMsgSubmissions->match_all($response);

		foreach($match[1] ?? [] as $v)
		{
			$id = (int) $v;
			if ($id > $last_id)
			{
				$submissions_ids[] = $id;
			}
		}

		$submissions_ids = array_unique($submissions_ids);
		sort($submissions_ids);

		return $submissions_ids;
	}

	/**
	 * Execute a cURL request to the given URL with optional POST data and proxy.
	 *
	 * @param string               $url       The full URL to request.
	 * @param bool                 $header    Whether to include the response headers (default: false).
	 * @param string|array|null    $postdata  Optional POST data to send with the request.
	 *
	 * @return string  The response body on success.
	 *
	 * @throws TimeOut       If the server did not respond within the timeout limit.
	 * @throws BadRespond    If the response is blocked by Cloudflare or malformed.
	 */
	protected function curl(string $url, bool $header = false, string|array|null $postdata = null): string
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, (($header) ? 1 : 0));
		curl_setopt($ch, CURLOPT_URL, $url);

		if ($this->proxy !== null)
		{
			$proxy = explode(":", $this->proxy);
			if (count($proxy) === 2)
			{
				curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
				curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[1]);
			}
		}

		if (isset($postdata))
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
		curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);

		$result = curl_exec($ch);
		curl_close($ch);

		// wait before next request
		if($this->crawl_delay > 0)
		{
			sleep($this->crawl_delay);
		}

		if (!$result)
		{
			throw new TimeOut("The server didn't respond for a certain time");
		}

		// fix for invalid utf-8 symbols
		$result = iconv('UTF-8', 'UTF-8//IGNORE', $result);

		if (Regex::Cloudflare->match($result) && Regex::CloudflareRayID->match($result))
		{
			throw new BadRespond("Cloudflare blocking site view");
		}

		if ($result && !Regex::FAName->match($result))
		{
			throw new BadRespond("The content is not loaded");
		}

		return $result;
	}

	/**
	 * Decode HTML entities in a string, including cases with multiple levels of encoding.
	 *
	 * This method repeatedly applies html_entity_decode() until the string
	 * stops changing, ensuring proper decoding when entities are double-encoded
	 * (e.g., "&amp;quot;" → "&quot;" → '"').
	 *
	 * @param string $s  The input string containing HTML entities.
	 *
	 * @return string  The fully decoded, human-readable string.
	 */
	protected function decodeHtmlEntitiesDeep(string $s): string
	{
		$prev = null;
		while ($prev !== $s) {
			$prev = $s;
			$s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		return $s;
	}
}
