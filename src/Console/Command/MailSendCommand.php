<?php
/**
 * Votix. The advanced and secure online voting platform.
 *
 * @author Club*Nix <club.nix@edu.esiee.fr>
 *
 * @license MIT
 */
namespace App\Console\Command;

use App\Entity\Voter;
use App\Repository\VoterRepository;
use App\Service\MailerService;
use App\Service\StatsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class VotixMailSendCommand
 */
class MailSendCommand extends Command
{
    private $logger;

    private $voterRepository;

    private $statsService;

    private $mailerService;

    private $mailer;

    private $senderMail;

    public function __construct(
        LoggerInterface $logger,
        VoterRepository $voterRepository,
        StatsService $statsService,
        MailerService $mailerService,
        \Swift_Mailer $mailer,
        string $votixSenderMail
    ) {
        $this->logger = $logger;
        $this->voterRepository = $voterRepository;
        $this->statsService = $statsService;
        $this->mailerService = $mailerService;
        $this->mailer = $mailer;
        $this->votixSenderMail = $votixSenderMail;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('votix:mail:send')
            ->setDescription('Send mail')
            ->addArgument(
                'template',
                InputArgument::REQUIRED,
                'Template to use'
            )
            ->addOption(
                'execute',
                null,
                InputOption::VALUE_NONE,
                'If set will send mail for real, otherwise dry-run only'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $template    = $input->getArgument('template');
        $mustExecute = $input->getOption('execute');

        /** @var Voter[] $voters */
        $voters = $this->voterRepository->findBy(['ballot' => null]);

        $stats = $this->statsService->getStats();

        $nb_mails_to_send = $stats['nb_invites'] - $stats['nb_votants'];

        $this->logger->info('Number of mails to send : {nb_mails_to_send}', ['nb_mails_to_send' => $nb_mails_to_send]);

        $counter = 1;

        foreach ($voters as $voter) {
            $info = '> ' . $counter . '/' . $nb_mails_to_send . ' ' . $voter->getFirstname() . ' '. $voter->getLastname() . ' <' . $voter->getEmail() . '>';

            $this->logger->info($info);

            $email = $this->mailerService->getTemplatedEmail($voter, $template, $this->votixSenderMail);

            if ($mustExecute) {
                $emailSentId = $this->mailer->send($email);
                //$message = 'Message envoyé à ' . $email->getTo()[0];
                //$this->logger->info($message, ['mail' => $emailSentId->getAll()]);

                usleep(200000);
            } else {
                $this->logger->notice('Mail not sent because we are running in dry-run');
            }

            $counter++;
        }

        return 0;
    }
}
