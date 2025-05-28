<?php

namespace LCSEngine\StateMachine;

interface StateInterface
{
    /**
     * Execute the state logic.
     */
    public function execute(StateFlowPacket $packet): void;

    /**
     * Check if this is an end state.
     */
    public function isEnd(): bool;

    /**
     * Get the name of the next state.
     */
    public function getNext(): ?string;

    /**
     * Determine the next state based on the given input.
     */
    public function determineNext(array $input): ?string;
}
