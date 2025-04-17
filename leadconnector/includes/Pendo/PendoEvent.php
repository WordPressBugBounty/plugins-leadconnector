<?php 

class PendoEvent{
    private string $eventName;
    private array $eventAttributes;

    private LeadConnector_Admin $leadConnector_admin;

    private string $pendoEndPoint = 'wordpress/lc-plugin/pendo/track'; 

    /**
     * Constructor for the PendoEvent
     * @param string $eventName The name of the event
     * @param array $eventAttributes K,V pairs of the event attributes
     * @return static 
     */
    public function __construct(string $eventName, array $eventAttributes){
        $this->eventName = $eventName;
        $this->eventAttributes = $eventAttributes;
        $this->leadConnector_admin = new LeadConnector_Admin(LEAD_CONNECTOR_PLUGIN_NAME, LEAD_CONNECTOR_VERSION);
        return $this;
    }
    /**
     * Function to add an attribute to the event
     * @param string $attributeName
     * @param mixed $attributeValue
     * @return void
     */
    public function addEventAttribute(string $attributeName, $attributeValue){
        $this->eventAttributes[$attributeName] = $attributeValue;
        return $this;
    }
    /**
     * Function to add multiple attributes to the event
     * @param array $eventAttributes
     * @return void
     */
    public function addEventAttributes(array $eventAttributes){
        $this->eventAttributes = array_merge($this->eventAttributes, $eventAttributes);
        return $this;
    }

    private function getAccountId(): string{
        $leadConnectorOptions = get_option(LEAD_CONNECTOR_OPTION_NAME);
        if(empty($leadConnectorOptions)){
            return null;
        }
        return $leadConnectorOptions[lead_connector_constants\lc_options_location_id];
    }
    
    private function getAdditionalEventAttributes(): array{
        return array(
            'remote_ip' => $_SERVER['REMOTE_ADDR'],
            'host' => $_SERVER['HTTP_HOST'],
        );
    }

    /**
     * Creates the event body that is sent to Backend
     * @return array
     */
    private function createEventBody(): Array{
        $body = Array(
            'eventName' => $this->eventName,
            'eventAttributes' => array_merge($this->eventAttributes, $this->getAdditionalEventAttributes()),
            'accountId' => $this->getAccountId(),
        );
        return $body;

    }
    /**
     * Sends the event to the backend
     * @return bool True if the event was sent successfully, false otherwise
     */
    public function send(): bool{
        try{
            try{
                $endpoint = $this->pendoEndPoint . "/" . $this->getAccountId();
                $response = $this->leadConnector_admin->lc_oauth_wp_remote_v2('post', $endpoint, $this->createEventBody());
            }
            catch(Exception $e){
                do_action("qm/error", $e);
                return false;
            }
            return true;
        }
        catch(Exception $e){
            do_action("qm/error", $e);
            return false;
        }
    }

}