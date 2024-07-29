<?php

/*********************************************************************
 * This Code is licensed under the GPL-3.0 License and is Part of a
 * ILIAS Plugin developed by sr solutions ag in Switzerland.
 *
 * https://sr.solutions
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\ResourceStorage\Flavour\Definition\CropToSquare;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class ilObjPhotoGalleryCropToSquare extends CropToSquare
{
    public function __construct(int $max_size = 512, int $quality = 75)
    {
        parent::__construct(true, $max_size, $quality);
    }

    public function getId(): string
    {
        return 'xphg_crop_to_square';
    }

}
