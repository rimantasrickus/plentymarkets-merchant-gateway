<?php

namespace HeidelpayMGW\Containers;

use Plenty\Plugin\Templates\Twig;

class HeidelpayMGWScriptsContainer
{
    public function call(Twig $twig)
    {
        return $twig->render('HeidelpayMGW::content.HeidelpayMGWScripts', []);
    }
}
