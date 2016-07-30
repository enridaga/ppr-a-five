<?php

namespace Propagation\Model;

interface PropagationRules {
	public function query($relation = NULL, $policy = NULL, $holds = NULL);
}