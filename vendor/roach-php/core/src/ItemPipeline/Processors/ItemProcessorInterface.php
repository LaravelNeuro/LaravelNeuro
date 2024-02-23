<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/roach-php/roach
 */

namespace RoachPHP\ItemPipeline\Processors;

use RoachPHP\ItemPipeline\ItemInterface;
use RoachPHP\Support\ConfigurableInterface;

interface ItemProcessorInterface extends ConfigurableInterface
{
    public function processItem(ItemInterface $item): ItemInterface;
}
