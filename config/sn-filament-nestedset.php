<?php

// config for Wsmallnews/FilamentNestedset
return [
    /**
     * Restrict deletion of nodes with children.
     */
    'allow_delete_parent' => false,

    /*
     * Restrict deletion of root nodes, even if 'allow_delete_parent' is true, root nodes can be deleted.
     */
    'allow_delete_root' => false,

    /**
     * create action show parent select field
     */
    'create_action_modal_show_parent_select' => true,

    /**
     * Display the "Create Child Node" action in each row (if 'create_action_modal_show_parent_select' is false, This field should be set to true)
     */
    'show_create_child_node_action_in_row' => true,

    /**
     * By default, the CSS file will be automatically loaded globally. If you use a filament custom theme, you can disable the automatic loading of the CSS file
     */
    'autoload_assets' => true,
];
