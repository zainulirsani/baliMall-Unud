<?php

namespace App\Service;

/**
 * Cart: A very simple PHP cart library.
 *
 * Copyright (c) 2017 Sei Kan
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   2017 Sei Kan <seikan.dev@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * @see         https://github.com/seikan/Cart
 * @see         https://github.com/gsmahardika/SimpleCart [FORK]
 */
class CartService
{
    /**
     * An unique ID for the cart.
     *
     * @var string
     */
    protected $cartId;

    /**
     * Maximum item allowed in the cart.
     *
     * @var int
     */
    protected $cartMaxItem = 0;

    /**
     * Maximum quantity of a item allowed in the cart.
     *
     * @var int
     */
    protected $itemMaxQuantity = 0;

    /**
     * Enable or disable cookie.
     *
     * @var bool
     */
    protected $useCookie = false;

    /**
     * A collection of cart items.
     *
     * @var array
     */
    private $items = [];

    /**
     * Initialize cart.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['cart_max_item']) && preg_match('/^\d+$/', $options['cart_max_item'])) {
            $this->cartMaxItem = $options['cart_max_item'];
        }

        if (isset($options['item_max_quantity']) && preg_match('/^\d+$/', $options['item_max_quantity'])) {
            $this->itemMaxQuantity = $options['item_max_quantity'];
        }

        if (isset($options['use_cookie']) && $options['use_cookie']) {
            $this->useCookie = (bool) $options['use_cookie'];
        }

        $this->cartId = md5(($_SERVER['HTTP_HOST'] ?? 'BaliMallCartService')).'_cart';
        $this->read();
    }

    public function getCartId(): string
    {
        return $this->cartId;
    }

    public function overrideCart(int $userId): void
    {
        if ($userId > 0) {
            $oldCartId = $this->cartId;
            $newCartId = md5(sprintf('BaliMallUserID%d', $userId)).'_cart';

            if ($oldCartId !== $newCartId) {
                $this->cartId = $newCartId;
                $this->read();
                $this->write();

                if ($this->useCookie) {
                    setcookie($oldCartId, '', -1);
                } else {
                    unset($_SESSION['_sf2_attributes'][$oldCartId]);
                }
            }
        }
    }

    /**
     * Get items in cart.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get item by id from cart.
     *
     * @param string $id
     * @param array  $attributes
     *
     * @return array
     */
    public function getItem(string $id, array $attributes = []): array
    {
        if ($this->isItemExists($id, $attributes)) {
            return $this->items[$id][0];
        }

        return [];
    }

    /**
     * Check if the cart is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty(array_filter($this->items));
    }

    /**
     * Get the total of item in cart.
     *
     * @return int
     */
    public function getTotalItem(): int
    {
        $total = 0;

        foreach ($this->items as $items) {
            foreach ($items as $item) {
                ++$total;
            }
        }

        return $total;
    }

    /**
     * Get the total of item quantity in cart.
     *
     * @return int
     */
    public function getTotalQuantity(): int
    {
        $quantity = 0;

        foreach ($this->items as $items) {
            foreach ($items as $item) {
                $quantity += $item['quantity'];
            }
        }

        return $quantity;
    }

    /**
     * Get the sum of a attribute from cart.
     *
     * @param string $attribute
     *
     * @return int
     */
    public function getAttributeTotal(string $attribute = 'price'): int
    {
        $total = 0;

        foreach ($this->items as $items) {
            foreach ($items as $item) {
                if (isset($item['attributes'][$attribute])) {
                    $total += $item['attributes'][$attribute] * $item['quantity'];
                }
            }
        }

        return $total;
    }

    public function getAttributeTotalWithTax(string $attribute = 'price'): int
    {
        $totalWithTax = 0;

        foreach ($this->items as $items) {
            foreach ($items as $item) {
                if (isset($item['attributes'][$attribute])) {
                    $totalWithTax += ($item['attributes'][$attribute] * $item['quantity']) + $item['attributes']['tax_nominal'];
                }
            }
        }

        return $totalWithTax;
    }

    public function isPkpTransaction():bool
    {
        foreach ($this->items as $items) {
            foreach ($items as $item) {
                if (isset($item['attributes']['is_pkp']) && $item['attributes']['is_pkp'] === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getAttributeTotalStore(string $attribute = 'price', $is_pkp): int
    {
        $total = 0;
        foreach ($this->items as $items) {
            foreach ($items as $item) {
                if (isset($item['attributes'][$attribute]) && $item['attributes']['is_pkp'] === $is_pkp) {
                    $total += $item['attributes'][$attribute] * $item['quantity'];
                }
            }
        }

        return $total;
    }


    /**
     * Remove all items from cart.
     */
    public function clear(): void
    {
        $this->items = [];
        $this->write();
    }

    /**
     * Check if a item exist in cart.
     *
     * @param string $id
     * @param array  $attributes
     *
     * @return bool
     */
    public function isItemExists(string $id, array $attributes = []): bool
    {
        $attributes = array_filter($attributes);

        if (isset($this->items[$id])) {
            $hash = md5(json_encode($attributes));
            foreach ($this->items[$id] as $item) {
                if ($item['hash'] === $hash) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add item to cart.
     *
     * @param string $id
     * @param int    $quantity
     * @param array  $attributes
     *
     * @return bool
     */
    public function add(string $id, int $quantity = 1, array $attributes = []): bool
    {
        $quantity = (preg_match('/^\d+$/', (string) $quantity)) ? $quantity : 1;
        $attributes = array_filter($attributes);
        $hash = md5(json_encode($attributes));

        if ($this->cartMaxItem !== 0 && count($this->items) >= $this->cartMaxItem) {
            return false;
        }

        if (isset($this->items[$id])) {
            foreach ($this->items[$id] as $index => $item) {
                if ($item['hash'] === $hash) {
                    $this->items[$id][$index]['quantity'] += $quantity;

                    $tempQuantity = $this->items[$id][$index]['quantity'];

                    if ($this->itemMaxQuantity < $this->items[$id][$index]['quantity'] && $this->itemMaxQuantity !== 0) {
                        $tempQuantity = $this->itemMaxQuantity;
                    }

                    $this->items[$id][$index]['quantity'] = $tempQuantity;
                    $this->write();

                    return true;
                }
            }
        }

        $this->items[$id][] = [
            'id'         => $id,
            'quantity'   => ($quantity > $this->itemMaxQuantity && $this->itemMaxQuantity !== 0) ? $this->itemMaxQuantity : $quantity,
            'hash'       => $hash,
            'attributes' => $attributes,
        ];
        $this->write();
        return true;
    }

    /**
     * Update item quantity.
     *
     * @param string $id
     * @param int    $quantity
     * @param array  $attributes
     *
     * @return bool
     */
    public function update(string $id, int $quantity = 1, array $attributes = []): bool
    {
        $quantity = (preg_match('/^\d+$/', (string) $quantity)) ? $quantity : 1;

        if ($quantity < 1) {
            $this->remove($id, $attributes);

            return true;
        }

        if (isset($this->items[$id])) {
            $hash = md5(json_encode(array_filter($attributes)));

            foreach ($this->items[$id] as $index => $item) {
                if ($item['hash'] === $hash) {
                    $this->items[$id][$index]['quantity'] = $quantity;

                    $tempQuantity = $this->items[$id][$index]['quantity'];

                    if ($this->itemMaxQuantity < $this->items[$id][$index]['quantity'] && $this->itemMaxQuantity !== 0) {
                        $tempQuantity = $this->itemMaxQuantity;
                    }

                    $this->items[$id][$index]['quantity'] = $tempQuantity;
                    $this->write();

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove item from cart.
     *
     * @param string $id
     * @param array  $attributes
     *
     * @return bool
     */
    public function remove(string $id, array $attributes = []): bool
    {
        if (!isset($this->items[$id])) {
            return false;
        }

        if (empty($attributes)) {
            unset($this->items[$id]);

            $this->write();

            return true;
        }

        $hash = md5(json_encode(array_filter($attributes)));

        foreach ($this->items[$id] as $index => $item) {
            if ($item['hash'] === $hash) {
                unset($this->items[$id][$index]);

                // https://github.com/seikan/Cart/issues/13
                $this->items[$id] = array_values($this->items[$id]);
                $this->write();

                return true;
            }
        }

        return false;
    }

    /**
     * Destroy cart session.
     */
    public function destroy(): void
    {
        $this->items = [];

        if ($this->useCookie) {
            setcookie($this->cartId, '', -1);
        } else {
            unset($_SESSION['_sf2_attributes'][$this->cartId]);
        }
    }

    /**
     * Read items from cart session.
     */
    private function read(): void
    {
        $items = ($this->useCookie) ? $_COOKIE[$this->cartId] ?? '[]' : $_SESSION['_sf2_attributes'][$this->cartId] ?? '[]';
        $this->items = json_decode($items, true);
    }

    /**
     * Write changes into cart session.
     */
    private function write(): void
    {
        if ($this->useCookie) {
            // https://github.com/seikan/Cart/pull/12
            setcookie($this->cartId, json_encode(array_filter($this->items)), time() + 604800, '/');
        } else {
            $_SESSION['_sf2_attributes'][$this->cartId] = json_encode(array_filter($this->items));
        }
    }
}
