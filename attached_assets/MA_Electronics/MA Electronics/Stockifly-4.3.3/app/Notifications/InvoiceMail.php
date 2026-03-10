<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class InvoiceMail extends Notification implements ShouldQueue
{
    use Queueable;

    private $mailSubject;
    private $mailContent;
    private $pdfContent;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($subject, $content, $pdfContent)
    {
        $this->mailSubject = $subject;
        $this->mailContent = $content;
        $this->pdfContent = $pdfContent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->mailSubject)
            ->markdown('mail.invoice', ['content' => $this->mailContent])
            ->attachData(base64_decode($this->pdfContent), 'invoice.pdf', [
                'mime' => 'application/pdf',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
