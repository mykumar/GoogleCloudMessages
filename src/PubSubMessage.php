<?php

namespace Gcloud\GooglePubSub;

use Google_Client;
use Google_Service_Pubsub;
use Google_Service_Pubsub_PublishRequest;
use Google_Service_Pubsub_PubsubMessage;
use Illuminate\Http\Request;

abstract class PubSubMessage
{
    /** @var string environment App::environment() */
    protected $environment;

    /** @var string project Google project slug */
    protected $project;

    /** @var string routingKey Message routing key. e.g.  */
    protected static $routingKey;

    /** @var string version Message version */
    protected $version = 'v1';

    /** @var string entity Noun. Entity (or Object type). e.g. customer, tuxedo, shirt, or cart-item. */
    protected $entity;

    public function __construct($environment, $project)
    {
        $this->environment = $environment;
        $this->project = $project;
    }

    /**
     * The Pub Sub Routing Key is a message attribute. For example, "Customer Created"
     * is a system event. The PubSub Topic is the Customer entity - `production-v1-customer`.
     * The Routing Key includes the verb - `created`, `updated`, `deleted`. For example:
     *
     *      accounts.customer.created
     *
     * @param string $routingKey
     * @return bool
     */
    public static function handles($routingKey)
    {
        return strcasecmp(static::$routingKey, $routingKey) == 0;
    }


    /**
     * Publish this message to the PubSub Topic.
     *
     * @param mixed $data
     */
    public function publish($data)
    {
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setScopes([Google_Service_Pubsub::PUBSUB]);

        /** @var Google_Service_Pubsub pubsub */
        $pubsub = new Google_Service_Pubsub($client);

        /** @var Google_Service_Pubsub_PubsubMessage $message */
        $message = new Google_Service_Pubsub_PubsubMessage();

        $json = json_encode($data);
        $message->setData(base64_encode($json));
        $message->setAttributes([
            'routingKey' => static::$routingKey
        ]);

        /** @var Google_Service_Pubsub_PublishRequest $request */
        $request = new Google_Service_Pubsub_PublishRequest();
        $request->setMessages([$message]);

        $pubsub->projects_topics->publish(
            "projects/{$this->project}/topics/{$this->topic()}",
            $request
        );
    }

    /**
     * Handle inbound PubSub message.
     *
     * @param string $messageData Decoded message.data attribute.
     */
    abstract public function handle($messageData);

    /**
     * PubSub message Topic name following this convention:
     *
     *   {environment}-{version}-{entity}
     *
     * Override for customized topic names.
     *
     * @return string
     */
    public function topic()
    {
        $nubs = [];

        if (!empty($this->environment)) {
            $nubs[] = $this->environment;
        }

        if (!empty($this->version)) {
            $nubs[] = $this->version;
        }

        if (!empty($this->entity)) {
            $nubs[] = $this->entity;
        }

        return join('-', $nubs);
    }
}
