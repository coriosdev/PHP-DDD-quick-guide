<?php
// Laravel 12 Clean Architecture / DDD + CQRS Skeleton (CRUD only, no Event Sourcing)
// Directory Structure & Base Classes

/*
Folder Tree:

app/
├── Domain/
│   ├── Common/
│   │   └── ValueObjects/
│   │       └── BaseValueObject.php
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
│   │   └── Repositories/
│   │       └── <Aggregate>Repository.php
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

// app/Domain/<ContextName>/Entities/EntityBase.php
namespace App\Domain\<ContextName>\Entities;

abstract class EntityBase
{
    protected ?int $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}

// app/Domain/<ContextName>/Repositories/<Aggregate>RepositoryInterface.php
namespace App\Domain\<ContextName>\Repositories;

interface <Aggregate>RepositoryInterface
{
    public function save(<Aggregate> $entity): <Aggregate>;
    /** @return <Aggregate>[] */
    public function all(): array;
    public function find(int $id): ?<Aggregate>;
    public function delete(int $id): void;
}

// app/Domain/<ContextName>/Exceptions/DomainException.php
namespace App\Domain\<ContextName>\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException {}

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

class <Action>Handler extends UseCaseBase
{
    public function execute(<Action>Command $command): mixed
    {
        // perform CRUD via repositories
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

class <QueryName>Handler extends UseCaseBase
{
    public function execute(<QueryName>Query $query): mixed
    {
        // fetch data via repositories
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
use DateTimeImmutable;

class <Aggregate>Repository implements <Aggregate>RepositoryInterface
{
    public function save(<Aggregate> $entity): <Aggregate>
    {
        // map entity to model and persist
    }

    public function all(): array
    {
        // return array of entities
    }

    public function find(int $id): ?<Aggregate>
    {
        // find model and map to entity
    }

    public function delete(int $id): void
    {
        <EloquentModel>::destroy($id);
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

class ArchitectureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind interfaces to persistence implementations
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
