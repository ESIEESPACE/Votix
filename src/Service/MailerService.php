<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Service;

use App\Entity\Voter;
use Twig\Environment;

/**
 * Class MailerService
 */
class MailerService
{

    private $tokenService;

    /** @var Environment */
    private $templateEngine;

    public function __construct(TokenService $tokenService, Environment $templating)
    {
        $this->tokenService   = $tokenService;
        $this->templateEngine = $templating;
    }

    /**
     * @param Voter $voter
     * @param string $template
     *
     * @return \Swift_Message
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getTemplatedEmail(Voter $voter, string $template): array
    {
        $tokenService = $this->tokenService;

        $vars = [
            'voter' => $voter,
            'link'  => $tokenService->getLinkForVoter($voter),
            'code'  => $tokenService->getCodeForVoter($voter),
        ];

        $html  = $this->templateEngine->render('mails/' . $template . '.html.twig',  $vars);
        $title = $this->templateEngine->render('mails/' . $template . '.title.twig', $vars);

        return $this->getEmailForVoter($voter, $title, $html);
    }

    /**
     * @param Voter $voter
     * @param string $title
     * @param string $html
     *
     * @return \Swift_Message
     */
    private function getEmailForVoter(Voter $voter, string $title, string $html): array
    {
        return (new \Swift_Message($title))
            ->setFrom(['votix@clubnix.fr' => 'Votix'])
            ->setReplyTo(['votix@clubnix.fr' => 'Votix'])
            ->setTo([$voter->getEmail() => $voter->getFirstname() . ' ' . $voter->getLastname()])
            ->setBody($html, 'text/html');
    }
}