<?php
namespace CarloNicora\Minimalism\AsanaToday\Factories;

use CarloNicora\JsonApi\Objects\Link;
use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\Services\Asana\Asana;
use DateTime;
use DateTimeZone;
use Exception;

class AsanaTodayFactory
{
    /**
     * @param Asana $asana
     */
    public function __construct(
        private readonly Asana $asana,
    )
    {
    }

    /**
     * @return ResourceObject[]
     * @throws Exception
     */
    public function getWorkspaces(
    ): array
    {
        $response = [];

        foreach ($this->asana->getUser()?->getWorkspaces() as $workspace) {
            $workspaceResource = new ResourceObject(
                type: 'workspace',
                id: $workspace->getId(),
            );
            $workspaceResource->attributes->add(name: 'name', value: $workspace->getName());

            $response[] = $workspaceResource;
        }

        return $response;
    }

    /**
     * @param string $workspaceId
     * @param string $userTaskListId
     * @param int $timezoneOffset
     * @param string $workspaceName
     * @param string $workspaceLink
     * @return ResourceObject[]
     * @throws Exception
     */
    public function getMyTasksInWorkspace(
        string $workspaceId,
        string &$userTaskListId,
        int $timezoneOffset,
        string $workspaceName,
        string $workspaceLink,
    ): array
    {
        $response = [];

        $userTaskListId = $this->asana->users()->getMyTasksId(
            userId: $this->asana->getUser()?->getId(),
            workspaceId: $workspaceId,
        );

        foreach ($this->asana->tasks()->getMyTasks($userTaskListId) as $task){
            if ($task->getAssigneeSection() !== null && $task->getAssigneeSection()->canBeToday()){
                $taskResource = new ResourceObject(
                    type: 'task',
                    id: $task->getId(),
                );
                $taskResource->links->add(new Link(
                    name: 'asana',
                    href: 'https://app.asana.com/0/' . $userTaskListId . '/' . $task->getId()
                ));
                $taskResource->attributes->add(name: 'name', value: $task->getName());
                $taskResource->attributes->add(name: 'workspaceId', value: $workspaceId);
                $taskResource->attributes->add(name: 'workspaceName', value: $workspaceName);
                $taskResource->attributes->add(name: 'workspaceLink', value: 'https://app.asana.com/0/' . $userTaskListId . '/list');

                if ($task->getDueAt() !== null){
                    $dueAt = clone($task->getDueAt());

                    if ($timezoneOffset < 0){
                        if ($timezoneOffset > -999){
                            $dueAt->setTimezone(new DateTimeZone('+0' . abs($timezoneOffset/60*100)));
                        } else {
                            $dueAt->setTimezone(new DateTimeZone('+' . abs($timezoneOffset/60*100)));
                        }
                    } elseif ($timezoneOffset > 0){
                        if ($timezoneOffset < 999){
                            $dueAt->setTimezone(new DateTimeZone('-0' . abs($timezoneOffset/60*100)));
                        } else {
                            $dueAt->setTimezone(new DateTimeZone('-' . abs($timezoneOffset/60*100)));
                        }
                    }

                    $taskResource->attributes->add(name: 'dueAt', value: $dueAt->format('H:i'));
                }

                if ($task->getDueOn() !== null) {

                    $today = new DateTime();
                    $today->setTime( hour: 0, minute: 0);

                    $match_date = DateTime::createFromFormat( "Y-m-d", $task->getDueOn());
                    $match_date->setTime( hour: 0, minute: 0);

                    $diff = $today->diff( $match_date );
                    $diffDays = (integer)$diff->format( "%R%a" );

                    if ($diffDays < 0){
                        $taskResource->meta->add(name: 'isLate', value: true);
                        if ($diffDays === -1){
                            $taskResource->attributes->add(name: 'dueOn', value: 'Yesterday');
                        } else {
                            $taskResource->attributes->add(name: 'dueOn', value: $match_date->format('d M'));
                        }
                    } elseif ($diffDays < 7){
                        $taskResource->meta->add(name: 'isNear', value: true);
                        if ($diffDays === 0){
                            $taskResource->attributes->add(name: 'dueOn', value: 'Today');
                        } elseif ($diffDays === 1){
                            $taskResource->attributes->add(name: 'dueOn', value: 'Tomorrow');
                        } else {
                            $taskResource->attributes->add(name: 'dueOn', value: $match_date->format('l'));
                        }
                    } elseif ($match_date->format('Y') !== $today->format('Y')){
                        $taskResource->attributes->add(name: 'dueOn', value: $match_date->format('d M Y'));
                    } else {
                        $taskResource->attributes->add(name: 'dueOn', value: $match_date->format('d M'));
                    }
                }

                $response[] = $taskResource;
            }
        }

        return $response;
    }
}