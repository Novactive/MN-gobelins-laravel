<?php

namespace App\Controller\Admin\Translator;

use Symfony\Contracts\Translation\TranslatorInterface;

trait Translator
{
    protected TranslatorInterface $translator;

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    protected function translate(string $key): string
    {
        return $this->translator->trans($key, [], self::$translationDomain);
    }
}
