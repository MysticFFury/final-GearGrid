<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerContactController extends AbstractController
{
    #[Route('/api/customer/contact', name: 'api_customer_contact', methods: ['POST'])]
    public function contact(Request $request, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors[] = 'Please enter your name.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($subject === '') {
            $errors[] = 'Please enter a subject.';
        }
        if ($message === '') {
            $errors[] = 'Please enter a message.';
        }

        if ($errors !== []) {
            return $this->json(['error' => implode(' ', $errors)], Response::HTTP_BAD_REQUEST);
        }

        $fromEmail = $_ENV['MAILER_FROM'] ?? 'noreply@geargrid.local';
        $toEmail = $_ENV['CONTACT_TO'] ?? $fromEmail;
        $safeSubject = mb_substr($subject, 0, 140);
        $safeMessage = mb_substr($message, 0, 5000);

        try {
            $emailMessage = (new Email())
                ->from(new Address($fromEmail, 'GearGrid Contact'))
                ->to($toEmail)
                ->replyTo(new Address($email, $name !== '' ? $name : $email))
                ->subject('Customer Contact: ' . $safeSubject)
                ->text(
                    "New customer contact message\n\n"
                    . "Name: {$name}\n"
                    . "Email: {$email}\n"
                    . "Subject: {$safeSubject}\n\n"
                    . "Message:\n{$safeMessage}\n"
                );

            $mailer->send($emailMessage);
        } catch (TransportExceptionInterface $e) {
            return $this->json(
                ['error' => 'Could not send message. Please try again later.'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        return $this->json(['message' => 'Message sent successfully. We will get back to you soon.']);
    }
}
