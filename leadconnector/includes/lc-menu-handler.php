<?php


class LC_Menu_Handler{
    // Default Domain Name for translation
    private $default_domain_name = 'leadconnector';
    // Default Plugin Name
    private $plugin_display_name = LEAD_CONNECTOR_DISPLAY_NAME;
    // Default Slug for the Plugin
    private $plugin_default_slug = 'lc-plugin';
    // Render Area Callback For the Plugin
    private $render_area_callback = 'lead_connector_render_plugin_settings_page';

    // Location ID Of Site
    private static $locationId = null;

    private $render_sub_menu_items = false;


    // Capabilities - Default is manage_options
    private $Capabilites = array(
        'manage_options' => 'manage_options'
    );

    // Icons for the Menu Items
    private $Icons = array(
        'dashicons-dashboard' => 'dashicons-admin-links',
    );

    // Sub Menu Items To Show
    private $sub_menu_items;

    private function init_submenu_items(){
        
        $this->sub_menu_items = array(
            'dashboard' => array(
                'title' => "Dashboard",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 10,
                'is_external' => false,
            ),
            'funnels' => array(
                'title' => "Funnels",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 20,
                'is_external' => false,
            ),
            'forms' => array(
                'title' => "Forms",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 30,
                'is_external' => false,
            ),
            'emails' => array(
                'title' => "Email",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 30,
                'is_external' => false,
            ),
            'phone' => array(
                'title' => "Phone Numbers",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 30,
                'is_external' => false,
            ),
            'chat_widget' => array(
                'title' => "Chat Widget",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 30,
                'is_external' => false,
            ),
            'contacts' => array(
                'title' => "Contacts",
                'capability' => $this->Capabilites["manage_options"],
                'icon' => $this->Icons['dashicons-dashboard'],
                'position' => 10,
                'is_external' => true,
                'external_link' => lead_connector_constants\LC_BASE_URL . "/location/".self::$locationId."/contacts/smart_list/All"
            ),
        );
    }

   /**
     * Initialize the Menu Handler and Add the Menu Items
     */
    public function __construct(){
        // Store Location Id
        $options = get_option(LEAD_CONNECTOR_OPTION_NAME);
        if (!isset($options[lead_connector_constants\lc_options_location_id])) {
            self::$locationId = null;
            $this->render_sub_menu_items = false;
        }
        else{
            self::$locationId = $options['location_id'];
            if(self::$locationId !== ''){
                $this->render_sub_menu_items = true;
            }
        }
        // Intialize the Sub Menu Items
        $this->init_submenu_items();
        add_action("admin_menu", array($this, 'register_menu'), 20); // Adding 20 as priority to make sure it is added after the default menu items
    }
    
    /**
     * Makes External Links for the Plugin
     * @param mixed $href
     * @param mixed $content
     * @param mixed $class
     * @return string
     */
    public function make_external_link(string $href, string $content, string $class ) {
		return "<a href=\"$href\" class=\"$class\" target=\"_blank\" onclick=\"blur()\">$content<span class='dashicons dashicons-external' style='font-size: 17px;'></span></a>";
	}

    /**
     * Register the Menu Items, Adds the Main Menu Item and then loops through the sub menu items and adds them
     * @return void
     */
    public function register_menu(){
        // Add Main Menu Item
        add_menu_page(
            __($this->plugin_display_name, $this->default_domain_name),
            __($this->plugin_display_name, $this->default_domain_name), 
            $this->Capabilites["manage_options"],
            $this->plugin_default_slug,
            $this->render_area_callback,
            'dashicons-admin-generic',
            '58.5'
        );

        // Remove the Default Submenu Page for the Plugin
        add_action('admin_menu', function() {
            remove_submenu_page($this->plugin_default_slug, $this->plugin_default_slug);
        }, 99);
    
        if($this->render_sub_menu_items){
            // Add Sub Menu Items
            foreach($this->sub_menu_items as $slug => $item){
                $this->add_submenu_items($slug, $item);
            }
        }
    }

    /**
     * Adds the Sub Menu Items to the Plugin
     * @param mixed $parent_slug : Primary Slug for the Plugin
     * @param mixed $item : Sub Menu Item Defined in the init_submenu_items
     * @return void
     */
    private function add_submenu_items($parent_slug, $item){
        if($item['is_external']){
            $page_slug = "lc-li-sub-item".$item['title'];
            $callback = '';
        }
        else{
            $page_slug = $this->plugin_default_slug . '&tab=' . $parent_slug;
            $callback = $this->render_area_callback;
        }
        add_submenu_page(
            $this->plugin_default_slug,
            __($item['title'], $this->default_domain_name),
            $item['is_external'] == 1 ? $this->make_external_link($item['external_link'], __($item['title'], $this->default_domain_name), 'external_link') : __($item['title'], $this->default_domain_name),
            $item['capability'],
            $page_slug,
            $callback,
        );
    }
}