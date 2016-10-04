<?php

namespace Glooby\TaskBundle\Synchronizer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Glooby\TaskBundle\Entity\Schedule;
use Glooby\TaskBundle\Annotation\Schedule as Def;
use Glooby\TaskBundle\Entity\ScheduleRepository;
use Glooby\TaskBundle\Schedule\ScheduleRegistry;

/**
 * @author Emil Kilhage
 */
class ScheduleSynchronizer
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @var ScheduleRegistry
     */
    private $scheduleRegistry;

    /**
     * @var bool
     */
    private $force = false;

    /**
     * @param boolean $force
     */
    public function setForce(bool $force)
    {
        $this->force = $force;
    }

    /**
     * @param ScheduleRegistry $scheduleRegistry
     */
    public function setScheduleRegistry(ScheduleRegistry $scheduleRegistry)
    {
        $this->scheduleRegistry = $scheduleRegistry;
    }

    /**
     *
     */
    public function sync()
    {
        /** @var ScheduleRepository $repo */
        $repo = $this->doctrine->getManager()
            ->getRepository('GloobyTaskBundle:Schedule');

        $schedules = $this->scheduleRegistry->getSchedules();

        foreach ($schedules as $id => $def) {
            try {
                /** @var Schedule $schedule */
                $schedule = $repo->findByName($id);

                if ($this->force || $schedule->getVersion() !== $def->version) {
                    $this->update($schedule, $id, $def);
                }
            } catch (\Exception $e) {
                $schedule = new Schedule();
                $this->update($schedule, $id, $def);
                $this->doctrine->getManager()->persist($schedule);
            }
        }

        foreach ($repo->findNotInNames(array_keys($schedules)) as $schedule) {
            $this->doctrine->getManager()->remove($schedule);
        }

        $this->doctrine->getManager()->flush();
    }

    /**
     * @param Schedule $schedule
     * @param string $id
     * @param Def $def
     */
    private function update(Schedule $schedule, string $id, Def $def)
    {
        $schedule->setName($id);
        $schedule->setActive($def->active);
        $schedule->setRunEvery($def->runEvery);
        $schedule->setTimeout($def->timeout);
        $schedule->setParams($def->params);
        $schedule->setVersion($def->version);
    }
}