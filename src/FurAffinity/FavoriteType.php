<?php

namespace FurAffinity;

/**
 * Enum representing possible favorite actions on FurAffinity.
 *
 * - Fav: Add to favorites
 * - UnFav: Remove from favorites
 */
enum FavoriteType: string
{
	case Fav = 'fav';
	case UnFav = 'unfav';

	public function inverse(): self
	{
		return $this === self::Fav ? self::UnFav : self::Fav;
	}
}
