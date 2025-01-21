<?php

class rex_config_form_enhanced extends rex_config_form
{
    protected function loadBackendConfig()
    {
        parent::loadBackendConfig();

        // Test-Button hinzufügen
        $attrTest = ['type' => 'submit', 'internal::useArraySyntax' => false, 'internal::fieldSeparateEnding' => true];
        $this->addControlField(
            null,
            $this->addField('button', 'test_button', 'Test Button', $attrTest, false),
        );

        // weiterer Button
        $attrAnother = ['type' => 'submit', 'internal::useArraySyntax' => false, 'internal::fieldSeparateEnding' => true];
        $this->addControlField(
            null,
            $this->addField('button', 'another_button', 'Another Button', $attrAnother, false),
        );
    }


   protected function save()
    {
        if (rex_request('test_button', 'string', '') !== '') {
            // Logik für den Test-Button
            echo rex_view::success('Test Button wurde geklickt!');
             // hier kann aber nicht die normale speicherung erfolgen
              return true;
        }
    
        if (rex_request('another_button', 'string', '') !== '') {
            // Logik für den Another-Button
            echo rex_view::success('Another Button wurde geklickt!');
            // hier kann aber nicht die normale speicherung erfolgen
              return true;
        }


        return parent::save(); // Aufruf der ursprünglichen save()-Methode
    }
}


// Verwendung im Addon:
$form = rex_config_form_enhanced::factory('my_addon', 'Einstellungen');
$form->addTextField('my_setting', 'Meine Einstellung');
$form->show();
