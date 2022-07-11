<?php
namespace CarloNicora\Minimalism\AsanaToday\Models;

use Asana\Errors\AsanaError;
use CarloNicora\JsonApi\Objects\Link;
use CarloNicora\JsonApi\Objects\ResourceObject;
use CarloNicora\Minimalism\AsanaToday\Abstracts\AbstractAsanaTodayModel;
use CarloNicora\Minimalism\Enums\HttpCode;
use Exception;

class Index extends AbstractAsanaTodayModel
{
    /**
     * @return HttpCode
     * @throws Exception
     */
    public function get(
    ): HttpCode
    {
        if ($this->asana->isAuthorised()) {
            $this->view = 'Index';

            $this->document->addResourceList($this->asanaTodayFactory->getWorkspaces());

            $this->document->links->add(new Link(
                name: 'logout',
                href: $this->path->getUrl() . 'logout',
            ));

            /** @noinspection RepetitiveMethodCallsInspection */
            $this->document->meta->add(
                name: 'user',
                value: [
                    'id' => $this->asana->getUser()?->getId(),
                    'name' => $this->asana->getUser()?->getName(),
                    'avatar' => $this->asana->getUser()?->getAvatar(),
                ]
            );
        } else {
            $this->view = 'Login';

            $page = new ResourceObject(type: 'login');

            $page->links->add(new Link(
                name: 'login',
                href: $this->path->getUrl() . 'asana/start',
            ));

            $this->document->addResource($page);
        }

        return HttpCode::Ok;
    }
}