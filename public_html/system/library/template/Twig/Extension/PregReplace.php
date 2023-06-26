<?php
class Twig_Extension_PregReplace extends Twig_Extension
{

    public function getName()
    {
        return 'preg_replace';
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('preg_replace', function($subject, $pattern, $replacement, $limit = -1) {
                return preg_replace($pattern, $replacement, $subject, $limit);
            }),
        );
    }
}
