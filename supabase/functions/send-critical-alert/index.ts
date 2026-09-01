// Edge Function: send-critical-alert
//
// Called by a Postgres trigger (check_estoque_critico, see
// supabase/migrations/006_push_notifications.sql) whenever a ponto's stock
// crosses into critical territory. Sends a real web push notification to
// every device subscribed in push_subscriptions.
//
// Required secrets (Project Settings -> Edge Functions -> send-critical-alert
// -> Secrets, or `supabase secrets set`):
//   VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY  — from scripts/generate-icons.mjs's
//     sibling, see README "Notificações push"
//   PUSH_WEBHOOK_SECRET                  — any random string; must match the
//     value stored in Supabase Vault as 'push_webhook_secret'
// SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY are provided automatically by
// the Edge Functions runtime.

import { createClient } from 'npm:@supabase/supabase-js@2'
import webpush from 'npm:web-push@3'

const VAPID_PUBLIC_KEY = Deno.env.get('VAPID_PUBLIC_KEY')!
const VAPID_PRIVATE_KEY = Deno.env.get('VAPID_PRIVATE_KEY')!
const WEBHOOK_SECRET = Deno.env.get('PUSH_WEBHOOK_SECRET')!
const SUPABASE_URL = Deno.env.get('SUPABASE_URL')!
const SUPABASE_SERVICE_ROLE_KEY = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!

webpush.setVapidDetails('mailto:contato@gelattoiceco.com.br', VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY)

Deno.serve(async (req) => {
  if (req.headers.get('x-webhook-secret') !== WEBHOOK_SECRET) {
    return new Response('Unauthorized', { status: 401 })
  }

  const { ponto_nome, estoque_atual_kg, capacidade_kg } = await req.json()

  const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY)
  const { data: subs } = await supabase.from('push_subscriptions').select('*')

  const payload = JSON.stringify({
    title: `Estoque crítico: ${ponto_nome}`,
    body: `${Math.round(estoque_atual_kg)}kg de ${Math.round(capacidade_kg)}kg — repor com urgência.`,
    tag: 'gelatto-alerta',
  })

  const results = await Promise.allSettled(
    (subs ?? []).map(async (sub) => {
      try {
        await webpush.sendNotification(
          { endpoint: sub.endpoint, keys: { p256dh: sub.p256dh, auth: sub.auth } },
          payload,
        )
      } catch (err) {
        if (err?.statusCode === 410 || err?.statusCode === 404) {
          await supabase.from('push_subscriptions').delete().eq('id', sub.id)
        }
        throw err
      }
    }),
  )

  return new Response(JSON.stringify({ subscribers: results.length }), {
    headers: { 'Content-Type': 'application/json' },
  })
})
