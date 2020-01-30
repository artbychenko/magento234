<?php declare(strict_types=1);

/**
 * Theme registration file
 *
 * @author Artem Bychenko <artbychenko@gmail.com>
 * @package frontend/Andronoid/Default
 */
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::THEME,
    'frontend/Andronoid/Default',
    __DIR__
);
