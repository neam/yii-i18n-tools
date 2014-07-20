<?php

class ChoiceFormatHelper
{

    static public function toString($messagesArray, $lang)
    {
        $locale = CLocale::getInstance($lang);
        $_ = array();
        foreach ($messagesArray as $key => $t) {
            $_[] = $locale->pluralRules[$key] . "#" . $t;
        }
        return implode("|", $_);
    }

    static public function toArray($messagesString, $lang)
    {
        $locale = CLocale::getInstance($lang);
        $n = preg_match_all('/\s*([^#]*)\s*#([^\|]*)\|/', $messagesString . '|', $matches);
        $return = array();
        foreach ($locale->pluralRules as $pluralRule) {
            for ($i = 0; $i < $n; ++$i) {
                $expression = $matches[1][$i];
                $message = $matches[2][$i];
                if ($expression == $pluralRule) {
                    $return[$pluralRule] = $message;
                }
            }
            if (!isset($return[$pluralRule])) {
                $return[$pluralRule] = null;
            }
        }
        return $return;
    }

}