<?php

namespace HeidelpayMGW\Containers;

use Plenty\Plugin\Templates\Twig;

class HeidelpayScriptsContainer
{
    public function call(Twig $twig)
    {
        return $twig->render('HeidelpayMGW::content.HeidelpayScripts', []);
    }
}
