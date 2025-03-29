<?php

namespace FurAffinity;

/**
 * Enumeration of regular expressions used to parse FurAffinity HTML responses.
 *
 * Each case represents a specific pattern used in HTML scraping.
 */
enum Regex: string
{
	case Cloudflare        = '/DDoS protection by Cloudflare/ui';
	case CloudflareRayID   = '/Ray ID\:/ui';
	case DownloadLink      = '/<a href="([^"]+)">Download<\/a>/ui';
	case UsernameFromLink  = '/\/art\/([^\/]+)\/([0-9]+)\//ui';
	case TitleAndAuthor    = '/<meta property="og:title" content="([^"]+) by ([^"]+)" \/>/ui';
	case WatchUnWatch      = '/has been (added to|removed from) your watch list\!/ui';
	case CheckLogIn        = '/href="\/user\/([^\/]+)\/"/ui';
	case FAName            = '/(Fur Affinity|<title>System Error<\/title>|Crawl-delay)/ui';
	case UserNotFound      = '/This user cannot be found\./ui';
	case WatchList         = '/href="\/user\/([^\/]+)\/"[^>]+>(<span[^>]*>)*\s*([^<]+)\s*</ui';
	case NewSubmissions    = '/New Submissions/ui';
	case NewMsgSubmissions = '/href="\/view\/([0-9]+)\/"/ui';
	case CrawlDelay        = '/Crawl-delay:\s*([0-9\.]+)/ui';
	case TargetBlocked     = '/someone who has blocked you/ui';

	public function match(string $subject): ?array
	{
		$matches = [];
		return preg_match($this->value, $subject, $matches) ? $matches : null;
	}

	public function match_all(string $subject): ?array
	{
		$matches = [];
		return preg_match_all($this->value, $subject, $matches) ? $matches : null;
	}

	public static function match_key(string $subject, string $action, string $value): ?array
	{
		$matches = [];
		$value = preg_quote((string)$value);
		$action = preg_quote((string)$action);
		return preg_match("/href=\"\/$action\/$value\/\?key=([A-Za-z0-9]+)\"/ui", $subject, $matches) ? $matches : null;
	}
}
