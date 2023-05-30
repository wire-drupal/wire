<?php

namespace Drupal\wire;

trait WithPagination {

  public int $page = 0;

  public function initializeWithPagination(): void {
    $this->page = $this->resolvePage();
  }

  public function previousPage(): void {
    $this->setPage(max($this->page - 1, 0));
  }

  public function nextPage(): void {
    $this->setPage($this->page + 1);
  }

  public function gotoPage($page): void {
    $this->setPage($page);
  }

  public function resetPage(): void {
    $this->setPage(0);
  }

  public function setPage($page): void {
    if (is_numeric($page)) {
      $page = (int) $page;
      $page = max($page, 0);
    }
    $beforePaginatorMethod = 'updatingPaginators';
    $afterPaginatorMethod = 'updatedPaginators';

    $beforeMethod = 'updating' . $this->pagerId;
    $afterMethod = 'updated' . $this->pagerId;

    if (method_exists($this, $beforePaginatorMethod)) {
      $this->{$beforePaginatorMethod}($page, $this->pagerId);
    }

    if (method_exists($this, $beforeMethod)) {
      $this->{$beforeMethod}($page, NULL);
    }

    $this->page = $page;

    if (method_exists($this, $afterPaginatorMethod)) {
      $this->{$afterPaginatorMethod}($page, $this->pagerId);
    }

    if (method_exists($this, $afterMethod)) {
      $this->{$afterMethod}($page, NULL);
    }
  }

  public function resolvePage(): int {
    return (int) \Drupal::request()->query->get($this->pagerId, $this->page);
  }

}
