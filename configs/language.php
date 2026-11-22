<?php

function getCurrentLanguage(): string
{
    $language = $_COOKIE['novabank_language'] ?? 'fr';
    return in_array($language, ['fr', 'en'], true) ? $language : 'fr';
}
