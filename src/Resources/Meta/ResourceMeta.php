<?php declare(strict_types = 1);

namespace WebChemistry\Images\Resources\Meta;

use Nette\SmartObject;
use Nette\Utils\Image;
use WebChemistry\Images\Modifiers\IModifiers;
use WebChemistry\Images\Resolvers\IHashResolver;
use WebChemistry\Images\Resolvers\INamespaceResolver;
use WebChemistry\Images\Resources\IResource;

class ResourceMeta implements IResourceMeta {

	use SmartObject;

	/** @var IModifiers */
	protected $modifierContainer;

	/** @var IHashResolver */
	protected $hashResolver;

	/** @var INamespaceResolver */
	protected $namespaceResolver;

	/** @var IResource */
	protected $resource;

	/** @var array [callback, values, changeSignature, alias] */
	private $modifiers;

	/** @var array|null */
	private $signature = null;

	/** @var string|null */
	private $hashFolder = false;

	public function __construct(IResource $resource, IModifiers $modifierContainer,
								IHashResolver $hashResolver, INamespaceResolver $namespaceResolver) {
		$this->resource = $resource;
		$this->modifierContainer = $modifierContainer;
		$this->hashResolver = $hashResolver;
		$this->namespaceResolver = $namespaceResolver;

		$this->prepare();
	}

	public function getResource(): IResource {
		return $this->resource;
	}

	public function getNamespaceFolder(): ?string {
		return $this->namespaceResolver->resolve($this);
	}

	public function getHashFolder(): ?string {
		if ($this->hashFolder === false) {
			$this->hashFolder = $this->hashResolver->resolve($this);
		}

		return $this->hashFolder;
	}

	public function getSignature(): array {
		if ($this->signature === null) {
			$this->signature = [];
			foreach ($this->getModifiers() as [, $values, $changeSignature, $alias]) {
				if (isset($this->signature[$alias])) {
					continue;
				}
				if (!$changeSignature) {
					continue;
				}

				$this->signature[$alias] = $this->resource->getAliases()[$alias];
			}
		}

		return $this->signature;
	}

	public function hasModifiers(): bool {
		return (bool) $this->getModifiers();
	}

	protected function prepare(): void {
		foreach ($this->getModifiers() as [$callback, $values, $changeSignature]) {
			if (!$changeSignature) {
				array_unshift($values, $this->resource);
				$callback(...$values);
			}
		}
	}

	public function modify(Image $image) {
		foreach ($this->getModifiers() as [$callback, $values, $changeSignature]) {
			if ($changeSignature) {
				array_unshift($values, $image);
				$callback(...$values);
			}
		}
	}

	protected function getModifiers() {
		if (!$this->modifiers) {
			$this->modifiers = $this->modifierContainer->getModifiersByResource($this->resource);
		}

		return $this->modifiers;
	}

}