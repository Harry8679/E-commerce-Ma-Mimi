<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestEmailController extends AbstractController
{
    #[Route('/test-email', name: 'test_email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('harry@rhum-shop.com')              // ← Votre nom si vous voulez
            ->to('crudolph.ebang@gmail.com')           // ← Votre vrai email Gmail
            ->subject('Test Email depuis mon site Symfony')
            ->text('Ceci est un email de test.')
            ->html('<p>Ceci est un <strong>email de test</strong>.</p>');

        try {
            $mailer->send($email);
            return new Response('✅ Email envoyé avec succès ! Vérifiez Mailtrap sur : https://mailtrap.io/inboxes/2868863/messages');
        } catch (\Exception $e) {
            return new Response('❌ Erreur : ' . $e->getMessage());
        }
    }
}