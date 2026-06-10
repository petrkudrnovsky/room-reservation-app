<?php

namespace App\Command;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:reservation:update-statuses',
    description: 'Transitions reservation statuses: APPROVED→ACTIVE when time window starts, ACTIVE→EXPIRED when it ends.',
)]
class UpdateReservationStatusesCommand extends Command
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Prague'));
        $activated = 0;
        $expired = 0;

        $reservations = $this->reservationRepository->findBy(['status' => [
            Reservation::STATUS_APPROVED,
            Reservation::STATUS_ACTIVE,
        ]]);

        foreach ($reservations as $reservation) {
            $start = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $reservation->getStartDatetime()->format('Y-m-d H:i:s'),
                new \DateTimeZone('Europe/Prague')
            );
            $end = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $reservation->getEndDatetime()->format('Y-m-d H:i:s'),
                new \DateTimeZone('Europe/Prague')
            );

            if ($reservation->getStatus() === Reservation::STATUS_APPROVED
                && $start <= $now
                && $end >= $now
            ) {
                $reservation->setStatus(Reservation::STATUS_ACTIVE);
                $activated++;
            } elseif ($reservation->getStatus() === Reservation::STATUS_ACTIVE
                && $end < $now
            ) {
                $reservation->setStatus(Reservation::STATUS_EXPIRED);
                $expired++;
            }
        }

        $this->em->flush();

        $output->writeln("Activated: $activated, Expired: $expired");

        return Command::SUCCESS;
    }
}
