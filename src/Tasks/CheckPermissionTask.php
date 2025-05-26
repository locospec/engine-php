<?php

namespace LCSEngine\Tasks;

use LCSEngine\Exceptions\PermissionDeniedException;
use LCSEngine\StateMachine\ContextInterface;

class CheckPermissionTask extends AbstractTask implements TaskInterface
{
    protected ContextInterface $context;

    public function getName(): string
    {
        return 'check_permission';
    }

    public function setContext(ContextInterface $context): void
    {
        $this->context = $context;
    }

    public function execute(array $input, array $taskArgs = []): array
    {
        if (! isset($input['locospecPermissions'])) {
            throw new \RuntimeException('Permissions data not found in input');
        }

        $permissions = $input['locospecPermissions'];

        if (! $permissions['isPermissionsEnabled']) {
            return $input;
        }

        if (! $permissions['isUserAllowed']) {
            throw new PermissionDeniedException('User does not have required permissions');
        }

        return $input;
    }
}
