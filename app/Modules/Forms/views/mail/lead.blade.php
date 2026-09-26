{!! 'New lead for ' . $client->name !!}

Name:    {!! $lead->payload['name'] ?? '-' !!}
Phone:   {!! $lead->payload['phone'] ?? '-' !!}
Email:   {!! $lead->payload['email'] ?? '-' !!}

@if(!empty($lead->payload['message']))
Message:
{!! $lead->payload['message'] !!}

@endif
Received: {{ $lead->created_at->format('Y-m-d H:i') }}

Open this lead: {{ $url }}
