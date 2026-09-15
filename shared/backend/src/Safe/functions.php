<?php

namespace Safe;

/**
 * Minimal stub for the Safe\class_alias function required by the php-css-parser library.
 * This simply proxies to the native PHP class_alias function.
 */
function class_alias(string $original, string $alias): bool
{
    return \class_alias($original, $alias);
}

