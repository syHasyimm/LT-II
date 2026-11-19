@extends('errors.layout')

@section('code', '403')
@section('title', 'Akses ditolak')
@section('message', str_contains((string) $exception->getMessage(), 'This action') || blank($exception->getMessage())
    ? 'Halaman ini tidak tersedia untuk akun Anda. Area admin khusus panitia, dan pembina hanya dapat membuka data gudepnya sendiri.'
    : $exception->getMessage())
