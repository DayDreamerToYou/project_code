<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    protected string $subjectLine;
    protected string $bodyText;
    protected string $pdfContent;
    protected string $pdfFilename;

    public function __construct(string $subject, string $body, string $pdfContent, string $pdfFilename)
    {
        $this->subjectLine = $subject;
        $this->bodyText = $body;
        $this->pdfContent = $pdfContent;
        $this->pdfFilename = $pdfFilename;
    }

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->text('emails.invoice', ['body' => $this->bodyText])
            ->attachData($this->pdfContent, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
    }
}
