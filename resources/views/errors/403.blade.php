@extends('layouts.error', ['code' => 403, 'icon' => 'shield-alert'])

@section('title', __('errors.403_title'))
@section('message', __('errors.403_text'))
