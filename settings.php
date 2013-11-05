<?php

$options = array(
    'all' => get_string('allcourses', 'block_course_list'),
    'own' => get_string('owncourses', 'block_course_list')
);

$settings->add(new admin_setting_configtext(
    'block_elgg_community_elgg_url',
    get_string('elgg_url', 'block_elgg_community'),
    get_string('config_elgg_url', 'block_elgg_community'),
    ''    
));

$settings->add(new admin_setting_configtext(
    'block_elgg_community_public',
    get_string('public_key', 'block_elgg_community'),
    get_string('config_public_key', 'block_elgg_community'),
    ''
));

$settings->add(new admin_setting_configtext(
    'block_elgg_community_secret',
    get_string('secret_key', 'block_elgg_community'),
    get_string('config_secret_key', 'block_elgg_community'),
    ''
));
