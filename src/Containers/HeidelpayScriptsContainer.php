<?php

namespace Heidelpay\Containers;

use Plenty\Plugin\Templates\Twig;

class HeidelpayScriptsContainer
{
    public function call(Twig $twig)
    {
        return $twig->render('Heidelpay::content.HeidelpayScripts', []);
    }
}
