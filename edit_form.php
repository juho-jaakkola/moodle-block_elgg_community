<?php

class block_elgg_community_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('pluginname', 'block_elgg_community'));

        // Use block with Elgg
        $mform->addElement('selectyesno', 'config_use_groups', get_string('config_use_groups', 'block_elgg_community'));
        $mform->setDefault('config_use_groups', false);

        // Display discussions
        $mform->addElement('selectyesno', 'config_display_discussions', get_string('config_display_discussions', 'block_elgg_community'));
        $mform->setDefault('config_display_discussions', false);

        // Display blogs
        $mform->addElement('selectyesno', 'config_display_blogs', get_string('config_display_blogs', 'block_elgg_community'));
        $mform->setDefault('config_display_blogs', false);

        // Display files
        $mform->addElement('selectyesno', 'config_display_files', get_string('config_display_files', 'block_elgg_community'));
        $mform->setDefault('config_display_files', false);
    }
}
