<?php

namespace App\Policies;


use App\Models\Product;
use App\Models\User;

class ProductPolicy {
    public function viewAny(User $u){ return true; }
    public function view(User $u, Product $p){ return $p->company_id === $u->company_id; }
    public function create(User $u){ return true; }
    public function update(User $u, Product $p){ return $p->company_id === $u->company_id; }
    public function delete(User $u, Product $p){ return $p->company_id === $u->company_id; }
}