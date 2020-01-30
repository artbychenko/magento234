<?php declare(strict_types=1);

/**
 * Module registration file
 *
 * @author Artem Bychenko <artbychenko@gmail.com>
 * @package Base_Store
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Base_Store',
    __DIR__
);
