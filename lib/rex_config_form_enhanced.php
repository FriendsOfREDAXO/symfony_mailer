<?php

class rex_config_form_enhanced extends rex_config_form
{
    /** @var array<string, array{label: string, attributes: array, callback: callable|null}> */
    private $buttons = [];

    /**
     * Fügt einen zusätzlichen Button zum Formular hinzu.
     *
     * @param string $name  Eindeutiger Name des Buttons
     * @param string $label Text auf dem Button
     * @param array $attributes Zusätzliche Attribute
     * @param callable|null $callback Optionaler Callback der ausgeführt wird
     *
     * @return $this
     */
    public function addButton(string $name, string $label, array $attributes = [], callable $callback = null): self
    {
        $this->buttons[$name] = [
            'label' => $label,
            'attributes' => $attributes,
            'callback' => $callback,
        ];

        return $this;
    }

    protected function loadBackendConfig()
    {
        parent::loadBackendConfig();

        // Buttons hinzufügen
        foreach ($this->buttons as $buttonName => $buttonData) {
            $attr = array_merge(
                ['type' => 'submit', 'internal::useArraySyntax' => false, 'internal::fieldSeparateEnding' => true],
                $buttonData['attributes']
            );

            $this->addControlField(
                null,
                $this->addField('button', $buttonName, $buttonData['label'], $attr, false),
            );
        }
    }

    protected function save()
    {
        foreach ($this->buttons as $buttonName => $buttonData) {
            if (rex_post($buttonName, 'string', '') !== '') {
                if (is_callable($buttonData['callback'])) {
                    return call_user_func($buttonData['callback']);
                }
            }
        }

        return parent::save();
    }
}
