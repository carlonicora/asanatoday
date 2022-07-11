<?php
namespace CarloNicora\Minimalism\AsanaToday\Models;

use Asana\Errors\AsanaError;
use CarloNicora\JsonApi\Objects\Link;
use CarloNicora\Minimalism\AsanaToday\Abstracts\AbstractAsanaTodayModel;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Parameters\PositionedParameter;
use Exception;

class TodayInWorkspace extends AbstractAsanaTodayModel
{
    /**
     * @param PositionedParameter $workspaceId
     * @param int $timezoneOffset
     * @return HttpCode
     * @throws Exception
     */
    public function get(
        PositionedParameter $workspaceId,
        int $timezoneOffset = 0,
    ): HttpCode
    {
        //$this->view = 'Tasks';

        session_write_close();

        try {
            $userTaskListId = '';
            $workspaceName = '';
            $workspaceLink = '';
            foreach ($this->asana->getUser()?->getWorkspaces() as $workspace) {
                if ($workspace->getId() === $workspaceId->getValue()){
                    $workspaceName = $workspace->getName();
                    $workspaceLink = 'https://app.asana.com/0/' . $userTaskListId . '/list';
                    break;
                }
            }

            $tasks = $this->asanaTodayFactory->getMyTasksInWorkspace(
                workspaceId: $workspaceId->getValue(),
                userTaskListId: $userTaskListId,
                timezoneOffset: $timezoneOffset,
                workspaceName: $workspaceName,
                workspaceLink: $workspaceLink,
            );

            if ($tasks !== []) {

                $this->document->meta->add(
                    name: 'workspaceName',
                    value: $workspaceName,
                );
                $this->document->links->add(
                    new Link(
                        name: 'workspace',
                        href: 'https://app.asana.com/0/' . $userTaskListId . '/list',
                    )
                );
                $this->document->addResourceList(
                    $tasks
                );
            }
        } catch (AsanaError $e) {
            if ($e->status === 401){
                throw new MinimalismException(status: HttpCode::Unauthorized, message: 'Asana needs to be reloaded');
            }
        }

        return HttpCode::Ok;
    }
}