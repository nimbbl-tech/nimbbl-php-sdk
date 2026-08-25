<?php

namespace Nimbbl\Api\Common;

/**
 * Canonical `event_type` values emitted by the Nimbbl backend on webhooks and callbacks.
 *
 * These are the wire values (the string carried in the payload's `event_type` field),
 * mirroring the backend dispatcher enum
 * (cachier-and-queue-consumers: tech.nimbbl.payments.cachier.enums.WebhookEvents).
 *
 * The REFUND_EVENTS and PAYMENT_LINK_EVENTS groups drive signature-format routing in
 * SignatureVerifier (each family is verified with a different per-field format). Payment
 * and pre-auth events (payment_*, capture_*, void_*, payment_authorized) carry a normal
 * transaction object and use the default payment signature format, so they are not grouped.
 */
class WebhookEvents
{
    // Payment
    const PAYMENT_SUCCESS = 'payment_success';
    const PAYMENT_FAILED = 'payment_failed';

    // Pre-authorization lifecycle
    const PAYMENT_AUTHORIZED = 'payment_authorized';
    const CAPTURE_PENDING = 'capture_pending';
    const CAPTURE_SUCCESS = 'capture_success';
    const CAPTURE_FAILED = 'capture_failed';
    const VOID_PENDING = 'void_pending';
    const VOID_SUCCESS = 'void_success';
    const VOID_FAILED = 'void_failed';

    // Refund
    const REFUND_PENDING = 'refund_pending';
    const REFUND_SUCCESS = 'refund_success';
    const REFUND_FAILED = 'refund_failed';

    // Payment Link
    const PAYMENT_LINK_PAID = 'payment_link_paid';
    const PAYMENT_LINK_EXPIRED = 'payment_link_expired';
    const PAYMENT_LINK_CANCELLED = 'payment_link_cancelled';
    const PAYMENT_LINK_SENT = 'payment_link_sent';
    const PAYMENT_LINK_OPENED = 'payment_link_opened';
    const PAYMENT_LINK_CREATED = 'payment_link_created';

    /**
     * Refund event_types — verified with the refund per-field signature format.
     */
    const REFUND_EVENTS = [
        self::REFUND_PENDING,
        self::REFUND_SUCCESS,
        self::REFUND_FAILED,
    ];

    /**
     * Payment-link event_types — verified with the payment-link signature format.
     */
    const PAYMENT_LINK_EVENTS = [
        self::PAYMENT_LINK_PAID,
        self::PAYMENT_LINK_EXPIRED,
        self::PAYMENT_LINK_CANCELLED,
        self::PAYMENT_LINK_SENT,
        self::PAYMENT_LINK_OPENED,
        self::PAYMENT_LINK_CREATED,
    ];
}
