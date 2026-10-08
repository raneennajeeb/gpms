@extends('layouts.error', ['code' => 429, 'icon' => 'clock'])

@section('title', __('errors.429_title'))
@section('message', __('errors.429_text'))
