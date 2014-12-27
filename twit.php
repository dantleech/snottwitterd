<?php
require 'vendor/autoload.php';
 
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Subscriber\Log\LogSubscriber;
use GuzzleHttp\Subscriber\Log\Formatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

if (!file_exists(__DIR__ . '/config.php')) {
    die('No config.php file found');
}

$userConfig = require_once(__DIR__ . '/config.php');

$config = array_merge(array(
    'timeout' => 120,
    'cache_dir' => __DIR__ . '/cache',
    'consumer_key' => '',
    'consumer_secret' => '',
    'token' => '',
    'token_secret' => '',
    'logging' => false,
), $userConfig);

if (!file_exists($config['cache_dir'])) {
    mkdir($config['cache_dir']);
}

$client = new Client(['base_url' => 'https://api.twitter.com', 'defaults' => ['auth' => 'oauth']]);

$oauth = new Oauth1([
    'consumer_key'    => $config['consumer_key'],
    'consumer_secret' => $config['consumer_secret'],
    'token'           => $config['token'],
    'token_secret'    => $config['token_secret'],
]);
$client->getEmitter()->attach($oauth);

if ($config['logging']) {
    $log = new Logger('guzzle');
    $log->pushHandler(new StreamHandler('guzzle.log'));  // Log will be found at a file named guzzle.log
    $subscriber = new LogSubscriber($log, Formatter::SHORT); //To see full details, you can use Formatter::DEBUG
}

$client->getEmitter()->attach($subscriber);
 
/*
 * Executing a GET request on the timeline service, pass the result to the json parser
 */

$oldTwits = null;

while (true) {
    try {
        $twits = $client->get('1.1/statuses/home_timeline.json')->json();

        if ((null !== $oldTwits && count($oldTwits) != count($twits))) {
            $newTwits = array_slice($twits, count($oldTwits));
            foreach ($twits as $newTwit) {
                $body = $newTwit['text'];
                if (isset($newTwit['retweet_status'])) {
                    $title = sprintf(
                        '%s retweeted %s',
                        $newTwit['user']['screen_name'],
                        $newTwit['retweet_status']['user']['screen_name']
                    );
                    $icon = $newTwit['retweet_status']['user']['profile_image_url'];
                } else {
                    $title = sprintf('%s', $newTwit['user']['screen_name']);
                    $icon = $newTwit['user']['profile_image_url'];
                }

                notify($title, $body, get_image_path($icon));
            }
        }

        $oldTwits = $twits;
    } catch (GuzzleHttp\Exception\ClientException $e) {
        notify('Error', $e->getMessage());
    }

    sleep($config['timeout']);
}

function get_image_path($url)
{
    global $config;

    $path = $config['cache_dir'] . '/' . md5($url) . '.jpeg';

    if (file_exists($path)) {
        return $path;
    }

    $data = file_get_contents($url);
    file_put_contents($path, $data);

    return $path;
}

function notify($title, $message, $icon = null)
{
    exec(sprintf(
        'export DISPLAY=:0; notify-send %s %s -i %s',
        escapeshellarg($title),
        escapeshellarg($message),
        escapeshellarg($icon)
    ));
}
