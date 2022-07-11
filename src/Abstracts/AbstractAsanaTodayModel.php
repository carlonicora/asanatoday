<?php
namespace CarloNicora\Minimalism\AsanaToday\Abstracts;

use Asana\Errors\AsanaError;
use CarloNicora\JsonApi\Objects\Link;
use CarloNicora\Minimalism\Abstracts\AbstractModel;
use CarloNicora\Minimalism\AsanaToday\AsanaToday;
use CarloNicora\Minimalism\AsanaToday\Factories\AsanaTodayFactory;
use CarloNicora\Minimalism\AsanaToday\Models\Index;
use CarloNicora\Minimalism\Factories\MinimalismFactories;
use CarloNicora\Minimalism\Services\Asana\Asana;
use CarloNicora\Minimalism\Services\Path;
use Exception;

class AbstractAsanaTodayModel extends AbstractModel
{
    /** @var Path */
    protected Path $path;

    /** @var Asana */
    protected Asana $asana;

    /** @var AsanaToday  */
    protected AsanaToday $asanaToday;

    /** @var AsanaTodayFactory  */
    protected AsanaTodayFactory $asanaTodayFactory;

    /**
     * @param MinimalismFactories $minimalismFactories
     * @param string|null $function
     * @throws Exception
     */
    public function __construct(
        MinimalismFactories $minimalismFactories,
        ?string $function = null,
    )
    {
        parent::__construct(
            minimalismFactories: $minimalismFactories,
            function:$function,
        );

        $this->path = $minimalismFactories->getServiceFactory()->create(Path::class);
        $this->asana = $minimalismFactories->getServiceFactory()->create(Asana::class);
        $this->asanaToday = $minimalismFactories->getServiceFactory()->create(AsanaToday::class);

        $this->asanaTodayFactory = new AsanaTodayFactory(asana: $this->asana);

        $this->document->links->add(new Link(name: 'home', href: $this->path->getUrl()));

        if (get_class($this) === Index::class && $this->asana->isAuthorised()){
            try {
                $this->asana->refreshUser();
            } catch (AsanaError $e) {
                if ($e->status === 401){
                    header('Location: '. $this->path->getUrl() . 'asana/start');
                    exit;
                }
            }
        } elseif (get_class($this) !== Index::class && !$this->asana->isAuthorised()){
            header('Location: '. $this->path->getUrl());
            exit;
        }
    }
}