<?php
/**
 * UserEventListener.php
 * Created by anonymous on 09/02/16 18:34.
 */

namespace Componeint\Seneschal\Listeners;

use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Config\Repository;

class UserEventListener
{
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     * @return array
     */
    public function subscribe($events)
    {
        $events->listen('seneschal.user.login', 'Componeint\Seneschal\Listeners\UserEventListener@onUserLogin', 10);
        $events->listen('seneschal.user.logout', 'Componeint\Seneschal\Listeners\UserEventListener@onUserLogout', 10);
        $events->listen('seneschal.user.registered', 'Componeint\Seneschal\Listeners\UserEventListener@welcome', 10);
        $events->listen('seneschal.user.resend', 'Componeint\Seneschal\Listeners\UserEventListener@welcome', 10);
        $events->listen('seneschal.user.reset', 'Componeint\Seneschal\Listeners\UserEventListener@passwordReset', 10);
    }

    /**
     * Handle user login events.
     */
    public function onUserLogin($user)
    {
        //
    }

    /**
     * Handle user logout events.
     */
    public function onUserLogout()
    {
        //
    }

    /**
     * Send a welcome email to a new user.
     *
     * @param $user
     * @param $activated
     *
     * @return bool
     * @internal param string $email
     * @internal param int $userId
     * @internal param string $activationCode
     */
    public function welcome($user, $activated)
    {
        $subject       = $this->config->get('seneschal.subjects.welcome');
        $view          = $this->config->get('seneschal.email.views.welcome', 'Seneschal::emails.welcome');
        $data['hash']  = $user->hash;
        $data['code']  = $user->getActivationCode();
        $data['email'] = $user->email;

        if (!$activated) {
            $this->sendTo($user->email, $subject, $view, $data);
        }
    }

    /**
     * Email Password Reset info to a user.
     *
     * @param $user
     * @param $code
     *
     * @internal param string $email
     * @internal param int $userId
     * @internal param string $resetCode
     */
    public function passwordReset($user, $code)
    {
        $subject       = $this->config->get('seneschal.subjects.reset_password');
        $view          = $this->config->get('seneschal.email.views.reset', 'Seneschal::emails.reset');
        $data['hash']  = $user->hash;
        $data['code']  = $code;
        $data['email'] = $user->email;

        $this->sendTo($user->email, $subject, $view, $data);
    }

    /**
     * Convenience function for sending mail
     *
     * @param       $email
     * @param       $subject
     * @param       $view
     * @param array $data
     */
    private function sendTo($email, $subject, $view, $data = [])
    {
        $sender = $this->gatherSenderAddress();

        Mail::queue($view, $data, function ($message) use ($email, $subject, $sender) {
            $message->to($email)
                ->from($sender['address'], $sender['name'])
                ->subject($subject);
        });
    }

    /**
     * If the application does not have a valid "from" address configured, we should stub in
     * a temporary alternative so we have something to pass to the Mailer
     *
     * @return array|mixed
     */
    private function gatherSenderAddress()
    {
        $sender = config('mail.from', []);

        if (!array_key_exists('address', $sender) || is_null($sender['address'])) {
            return ['address' => 'noreply@example.com', 'name' => ''];
        }

        if (is_null($sender['name'])) {
            $sender['name'] = '';
        }

        return $sender;
    }
}