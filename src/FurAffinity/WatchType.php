<?php

namespace FurAffinity;

/**
 * Enum representing possible watch actions on FurAffinity.
 *
 * - Watch: Add user to watchlist
 * - UnWatch: Remove user from watchlist
 */
enum WatchType: string
{
	case Watch = 'watch';
	case UnWatch = 'unwatch';

	public function inverse(): self
	{
		return $this === self::Watch ? self::UnWatch : self::Watch;
	}
}
