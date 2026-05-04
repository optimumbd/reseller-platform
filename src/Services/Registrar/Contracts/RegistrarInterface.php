<?php

declare(strict_types=1);

namespace App\Services\Registrar\Contracts;

interface RegistrarInterface
{
    /** @return array{available:bool,premium?:bool,price?:float,currency?:string} */
    public function checkAvailability(string $domain): array;

    /** @return array{success:bool,registrar_id?:string,expires_at?:string,error?:string} */
    public function register(string $domain, int $years, array $contacts, array $nameservers = [], array $extra = []): array;

    /** @return array{success:bool,error?:string,expires_at?:string} */
    public function renew(string $domain, int $years): array;

    /** @return array{success:bool,transfer_id?:string,error?:string} */
    public function transfer(string $domain, string $authCode, array $contacts): array;

    public function getNameservers(string $domain): array;

    public function setNameservers(string $domain, array $hosts): bool;

    public function getEppCode(string $domain): ?string;

    public function setLock(string $domain, bool $locked): bool;

    public function setPrivacy(string $domain, bool $enabled): bool;

    public function setAutoRenew(string $domain, bool $enabled): bool;

    /** @return array<int,array{type:string,name:string,content:string,ttl:int,priority:?int}> */
    public function listDnsRecords(string $domain): array;

    public function setDnsRecords(string $domain, array $records): bool;
}
