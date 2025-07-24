<?php
// Laravel 12 Clean Architecture / DDD + CQRS Skeleton (CRUD only) with Strategy‑based Unit of Work
// Directory Structure & Base Classes

/*
Folder Tree:

app/
├── Domain/
│   ├── Common/
│   │   ├── Persistence/
│   │   │   ├── UnitOfWorkInterface.php
│   │   │   ├── EntityManagerInterface.php
│   │   │   └── EntityManagerRegistryInterface.php
│   │   ├── ValueObjects/
│   │   │   └── BaseValueObject.php
│   │   └── Exceptions/
│   │       └── DomainException.php
│   └── <ContextName>/
│       ├── Entities/
│       │   └── EntityBase.php
│       ├── ValueObjects/
│       ├── Repositories/
│       │   └── <Aggregate>RepositoryInterface.php
│       └── Exceptions/
│           └── DomainException.php
│
├── Application/
│   ├── Common/
│   │   ├── UseCaseBase.php   # base for CQRS handlers
│   │   └── DTOs/
│   │       └── BaseDTO.php
│   ├── Commands/             # write operations
│   │   └── <ContextName>/    # grouped by context
│   │       ├── <Action>Command.php
│   │       └── Handlers/
│   │           └── <Action>Handler.php
│   └── Queries/              # read operations
│       └── <ContextName>/    # grouped by context
│           ├── <QueryName>Query.php
│           └── Handlers/
│               └── <QueryName>Handler.php
│
├── Infrastructure/
│   ├── Persistence/
│   │   ├── Models/
│   │   │   └── <EloquentModel>.php
│   │   ├── Repositories/
│   │   │   └── <Aggregate>Repository.php
│   │   ├── UnitOfWork/
│   │   │   └── StrategyUnitOfWork.php
│   │   └── EntityManager/
│   │       ├── EloquentEntityManager.php
│   │       ├── RestApiEntityManager.php
│   │       └── EntityManagerRegistry.php
│   └── Adapters/
│       └── <Service>Adapter.php
│
├── Interfaces/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── BaseController.php
│   │   └── Requests/
│   │       └── BaseFormRequest.php
│   └── Console/
│       └── Commands/
│           └── CommandBase.php
│
├── Providers/
│   └── ArchitectureServiceProvider.php
└── Exceptions/
    └── ApplicationException.php
*/

// app/Domain/Common/ValueObjects/BaseValueObject.php
namespace App\Domain\Common\ValueObjects;

abstract class BaseValueObject
{
    public function equals(self $other): bool
    {
        return $this == $other;
    }
}

// app/Domain/Common/Exceptions/DomainException.php
namespace App\Domain\Common\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException {}

// app/Domain/Common/Persistence/EntityManagerInterface.php
namespace App\Domain\Common\Persistence;

interface EntityManagerInterface
{
    public function persist(object $entity): void;
    public function remove(object $entity): void;
    public function flush(): void;
}

// app/Domain/Common/Persistence/UnitOfWorkInterface.php
namespace App\Domain\Common\Persistence;

interface UnitOfWorkInterface
{
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;

    /**
     * Persist any tracked entities.
     */
    public function persistTracked(): void;
}

// app/Domain/Common/Persistence/EntityManagerRegistryInterface.php
namespace App\Domain\Common\Persistence;

interface EntityManagerRegistryInterface
{
    /**
     * Register an entity manager for a given entity class.
     */
    public function register(string $entityClass, EntityManagerInterface $manager): void;

    /**
     * Retrieve the manager responsible for this entity.
     */
    public function managerFor(object $entity): EntityManagerInterface;
}

// app/Domain/<ContextName>/Entities/EntityBase.php
namespace App\Domain\<ContextName>\Entities;

abstract class EntityBase
{
    protected ?int $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Hook for tracking state changes.
     */
    public function markDirty(): void {}
}

// app/Domain/<ContextName>/Repositories/<Aggregate>RepositoryInterface.php
namespace App\Domain\<ContextName>\Repositories;

use App\Domain\<ContextName>\Entities\<Aggregate>;

interface <Aggregate>RepositoryInterface
{
    public function save(<Aggregate> $entity): <Aggregate>;
    /** @return <Aggregate>[] */
    public function all(): array;
    public function find(int $id): ?<Aggregate>;
    public function delete(int $id): void;
}

// app/Application/Common/UseCaseBase.php
namespace App\Application\Common;

abstract class UseCaseBase
{
    abstract public function execute(object $dto): mixed;
}

// app/Application/Common/DTOs/BaseDTO.php
namespace App\Application\Common\DTOs;

abstract class BaseDTO
{
    public static function fromArray(array $data): static
    {
        return new static(...array_values($data));
    }
}

// app/Application/Commands/<ContextName>/<Action>Command.php
namespace App\Application\Commands\<ContextName>;

use App\Application\Common\DTOs\BaseDTO;

class <Action>Command extends BaseDTO
{
    // define public properties for command input
}

// app/Application/Commands/<ContextName>/Handlers/<Action>Handler.php
namespace App\Application\Commands\<ContextName>\Handlers;

use App\Application\Common\UseCaseBase;
use App\Application\Commands\<ContextName>\<Action>Command;
use App\Domain\Common\Persistence\UnitOfWorkInterface;

class <Action>Handler extends UseCaseBase
{
    public function __construct(
        private UnitOfWorkInterface $unitOfWork
    ) {}

    public function execute(<Action>Command $command): mixed
    {
        $this->unitOfWork->begin();
        try {
            // create or modify entities, call persist on registry
            $this->unitOfWork->persistTracked();
            $this->unitOfWork->commit();
            return /* result DTO or value */;
        } catch (\Throwable $e) {
            $this->unitOfWork->rollback();
            throw $e;
        }
    }
}

// app/Application/Queries/<ContextName>/<QueryName>Query.php
namespace App\Application\Queries\<ContextName>;

use App\Application\Common\DTOs\BaseDTO;

class <QueryName>Query extends BaseDTO
{
    // define public properties for query parameters
}

// app/Application/Queries/<ContextName>/Handlers/<QueryName>Handler.php
namespace App\Application\Queries\<ContextName>\Handlers;

use App\Application\Common\UseCaseBase;
use App\Application\Queries\<ContextName>\<QueryName>Query;
use App\Domain\<ContextName>\Repositories\<Aggregate>RepositoryInterface;

class <QueryName>Handler extends UseCaseBase
{
    public function __construct(
        private <Aggregate>RepositoryInterface $repo
    ) {}

    public function execute(<QueryName>Query $query): mixed
    {
        return $this->repo->all();
    }
}

// app/Infrastructure/Persistence/Models/<EloquentModel>.php
namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class <EloquentModel> extends Model
{
    protected $table = '<table_name>';
    protected $fillable = [/* columns */];
}

// app/Infrastructure/Persistence/Repositories/<Aggregate>Repository.php
namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\<ContextName>\Entities\<Aggregate>;
use App\Domain\<ContextName>\Repositories\<Aggregate>RepositoryInterface;
use App\Infrastructure\Persistence\Models\<EloquentModel>;

class <Aggregate>Repository implements <Aggregate>RepositoryInterface
{
    public function save(<Aggregate> $entity): <Aggregate>
    {
        // map to model, save; mark entity with ID; return entity
    }

    public function all(): array
    {
        // return entities mapped from all models
    }

    public function find(int $id): ?<Aggregate>
    {
        // find model by ID; map to entity
    }

    public function delete(int $id): void
    {
        <EloquentModel>::destroy($id);
    }
}

// app/Infrastructure/Persistence/UnitOfWork/StrategyUnitOfWork.php
namespace App\Infrastructure\Persistence\UnitOfWork;

use App\Domain\Common\Persistence\UnitOfWorkInterface;
use App\Domain\Common\Persistence\EntityManagerRegistryInterface;

class StrategyUnitOfWork implements UnitOfWorkInterface
{
    private array $tracked = [];

    public function __construct(
        private EntityManagerRegistryInterface $registry
    ) {}

    public function begin(): void
    {
        // no-op or start logging
    }

    public function commit(): void
    {
        $this->persistTracked();
    }

    public function rollback(): void
    {
        $this->tracked = [];
    }

    public function persistTracked(): void
    {
        foreach ($this->tracked as \$entity) {
            $manager = $this->registry->managerFor(\$entity);
            $manager->persist(\$entity);
        }
        // after persisting, clear tracked
        \$this->tracked = [];
    }

    /**
     * Add an entity to be persisted.
     */
    public function track(object \$entity): void
    {
        \$this->tracked[] = \$entity;
    }
}

// app/Infrastructure/Persistence/EntityManager/EloquentEntityManager.php
namespace App\Infrastructure\Persistence\EntityManager;

use App\Domain\Common\Persistence\EntityManagerInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentEntityManager implements EntityManagerInterface
{
    public function persist(object $entity): void
    {
        if ($entity instanceof Model) {
            $entity->save();
        }
    }

    public function remove(object $entity): void
    {
        if ($entity instanceof Model) {
            $entity->delete();
        }
    }

    public function flush(): void
    {
        // no-op for Eloquent
    }
}

// app/Infrastructure/Persistence/EntityManager/RestApiEntityManager.php
namespace App\Infrastructure\Persistence\EntityManager;

use App\Domain\Common\Persistence\EntityManagerInterface;

class RestApiEntityManager implements EntityManagerInterface
{
    public function persist(object $entity): void
    {
        // translate entity to API payload and send via HTTP
    }

    public function remove(object $entity): void
    {
        // call delete endpoint
    }

    public function flush(): void
    {
        // possibly no-op or finalize batch calls
    }
}

// app/Infrastructure/Persistence/EntityManager/EntityManagerRegistry.php
namespace App\Infrastructure\Persistence\EntityManager;

use App\Domain\Common\Persistence\EntityManagerRegistryInterface;
use App\Domain\Common\Persistence\EntityManagerInterface;

class EntityManagerRegistry implements EntityManagerRegistryInterface
{
    private array $managers = [];

    public function register(string $entityClass, EntityManagerInterface $manager): void
    {
        $this->managers[$entityClass] = $manager;
    }

    public function managerFor(object $entity): EntityManagerInterface
    {
        $class = get_class($entity);
        if (isset($this->managers[$class])) {
            return $this->managers[$class];
        }
        throw new \RuntimeException("No EntityManager registered for \$class");
    }
}

// app/Infrastructure/Adapters/<Service>Adapter.php
namespace App\Infrastructure\Adapters;

class <Service>Adapter
{
    // adapter for external service
}

// app/Interfaces/Http/Controllers/BaseController.php
namespace App\Interfaces\Http\Controllers;

use Illuminate\Routing\Controller as Base;

class BaseController extends Base
{
    protected function respondSuccess(mixed $data, int $status = 200)
    {
        return response()->json($data, $status);
    }

    protected function respondError(string $message, int $status)
    {
        return response()->json(['error' => $message], $status);
    }
}

// app/Interfaces/Http/Requests/BaseFormRequest.php
namespace App\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}

// app/Interfaces/Console/Commands/CommandBase.php
namespace App\Interfaces\Console\Commands;

use Illuminate\Console\Command;

abstract class CommandBase extends Command {}

// app/Providers/ArchitectureServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Common\Persistence\UnitOfWorkInterface;
use App\Infrastructure\Persistence\UnitOfWork\StrategyUnitOfWork;
use App\Domain\Common\Persistence\EntityManagerRegistryInterface;
use App\Infrastructure\Persistence\EntityManager\EntityManagerRegistry;
use App\Infrastructure\Persistence\EntityManager\EloquentEntityManager;
use App\Infrastructure\Persistence\EntityManager\RestApiEntityManager;

class ArchitectureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // EntityManager Registry
        $this->app->singleton(EntityManagerRegistryInterface::class, function($app) {
            $registry = new EntityManagerRegistry();
            // Register default managers
            $registry->register(App\Domain\<ContextName>\Entities\<Aggregate>::class, $app->make(EloquentEntityManager::class));
            $registry->register(App\Domain\User\Entities\User::class, $app->make(RestApiEntityManager::class));
            return $registry;
        });

        // UnitOfWork uses the registry
        $this->app->singleton(UnitOfWorkInterface::class, StrategyUnitOfWork::class);

        // Bind entity manager implementations
        $this->app->singleton(EloquentEntityManager::class);
        $this->app->singleton(RestApiEntityManager::class);

        // Bind repositories interfaces to implementations here
    }

    public function boot(): void
    {
        // load routes, config, etc.
    }
}

// app/Exceptions/ApplicationException.php
namespace App\Exceptions;

use RuntimeException;

class ApplicationException extends RuntimeException {}
