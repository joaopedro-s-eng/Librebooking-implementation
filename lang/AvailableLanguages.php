<?php

require_once(ROOT_DIR . 'lang/AvailableLanguage.php');

class AvailableLanguages
{
    /**
     * @return array|AvailableLanguage[]
     */
    public static function GetAvailableLanguages()
    {
        return [
            'pt_br' => new AvailableLanguage('pt_br', 'pt_br.php', 'Portugu&ecirc;s Brasileiro'),
        ];
    }

    public static function Contains($language)
    {
        $languages = self::GetAvailableLanguages();

        return array_key_exists(strtolower($language), $languages);
    }
}
