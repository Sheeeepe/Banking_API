<?php

namespace App\Http;

class TransactionQueryParams
{
    private const ALLOWED_TYPES  = ['deposit', 'withdrawal', 'transfer'];
    private const ALLOWED_SORTS  = ['created_at', 'amount'];
    private const ALLOWED_ORDERS = ['asc', 'desc'];

    public readonly ?string $type;
    public readonly string  $sort;
    public readonly string  $order;
    public readonly int     $page;
    public readonly int     $limit;
    public readonly ?string $from;
    public readonly ?string $to;

    private function __construct(
        ?string $type,
        string  $sort,
        string  $order,
        int     $page,
        int     $limit,
        ?string $from,
        ?string $to,
    ) {
        $this->type  = $type;
        $this->sort  = $sort;
        $this->order = $order;
        $this->page  = $page;
        $this->limit = $limit;
        $this->from  = $from;
        $this->to    = $to;
    }

    public static function fromRequest($request): self
    {
        $p = $request->getQueryParams();

        $type  = in_array($p['type']  ?? null, self::ALLOWED_TYPES,  true) ? $p['type']  : null;
        $sort  = in_array($p['sort']  ?? null, self::ALLOWED_SORTS,  true) ? $p['sort']  : 'created_at';
        $order = in_array($p['order'] ?? null, self::ALLOWED_ORDERS, true) ? $p['order'] : 'desc';
        $page     = max(1, (int) ($p['page'] ?? 1));
        $limitRaw = (int) ($p['limit'] ?? 20);
        $limit    = $limitRaw === 0 ? 0 : min(100, max(1, $limitRaw));

        $from = isset($p['from']) && self::isValidDate($p['from']) ? $p['from'] : null;
        $to   = isset($p['to'])   && self::isValidDate($p['to'])   ? $p['to']   : null;

        // Swap silently if from > to
        if ($from !== null && $to !== null && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return new self($type, $sort, $order, $page, $limit, $from, $to);
    }

    private static function isValidDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
