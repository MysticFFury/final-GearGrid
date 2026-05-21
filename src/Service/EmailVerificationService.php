<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $senderEmail = 'noreply@geargrid.com',
    ) {
    }

    public function sendVerificationEmail(User $user, string $verificationToken): void
    {
        // Generate the verification URL
        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'GearGrid'))
            ->to($user->getEmail())
            ->subject('Verify your email address - GearGrid')
            ->htmlTemplate('email/verification_email.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'verificationToken' => $verificationToken,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Generate a unique verification token
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
