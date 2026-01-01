# Supervisor Setup in DDEV

This project uses DDEV's built-in Supervisor to manage long-running background processes.

## Overview

DDEV's supervisor automatically manages the `articles:subscribe` command, ensuring it:
- ✅ Starts automatically when you run `ddev start`
- ✅ Restarts automatically if it crashes
- ✅ Logs all output via supervisor
- ✅ No complex retry logic needed in application code

**Current Status**: The `articles-subscribe` daemon is running and managed by supervisor!

## Quick Commands

```bash
# Check status of all supervisor processes
ddev supervisor
# or
ddev supervisor status

# Restart the articles subscriber
ddev supervisor restart

# View live logs
ddev supervisor tail
# or
ddev supervisor logs

# Stop/start the articles subscriber
ddev supervisor stop
ddev supervisor start
```

## How It Works

1. **DDEV Configuration** (`.ddev/config.yaml`)
   - `web_extra_daemons` tells DDEV to run `articles:subscribe` as a background process
   - DDEV automatically creates a supervisor config for this daemon
   - DDEV's built-in supervisor manages the process lifecycle

2. **Automatic Restarts**
   - If Redis disconnects, the command exits
   - Supervisor automatically restarts it
   - No complex retry logic needed in the application code

## Adding More Background Processes

To add a new background process, edit `.ddev/config.yaml`:

```yaml
web_extra_daemons:
  - name: articles-subscribe
    command: "php /var/www/html/artisan articles:subscribe"
    directory: /var/www/html
  - name: my-new-process
    command: "php /var/www/html/artisan my:command"
    directory: /var/www/html
```

Then restart DDEV:
```bash
ddev restart
```

## Logs

View logs using supervisor commands:
```bash
# View live output (recommended)
ddev supervisor tail

# Or view all DDEV web container logs
ddev logs -s web
```

### Testing the Subscriber

The easiest way to test is using the built-in test command:

```bash
# Send a test message to the default channel (articles:crime)
ddev test-redis-publish

# Or specify a different channel
ddev test-redis-publish articles:breaking
```

#### Manual Testing

To manually test from your HOST machine, publish directly to the channel (no prefix needed):

```bash
# Check the active subscription channels
redis-cli PUBSUB CHANNELS
# Output: articles:crime

# Publish a test message
redis-cli PUBLISH articles:crime '{"id":"test-123","title":"Test Article","canonical_url":"https://example.com/test","source":"test","published_date":"2025-12-31","publisher":{"name":"Test Publisher"}}'
```

You should see `(integer) 1` indicating 1 subscriber received the message, and in the logs:
```
✓ Dispatched article: Test Article
```

**Note:** The Redis prefix has been disabled (set to empty string in `.ddev/config.yaml`). If you enable it again, you'll need to include the prefix in your channel names.

## Development Workflow

- **Running `ddev dev`**: Starts Horizon, Pail, and Vite (but NOT articles:subscribe)
- **articles:subscribe**: Runs independently via supervisor in the background
- **To stop everything**: `ddev stop` stops all services including supervisor

## Troubleshooting

### Check if supervisor is running
```bash
ddev supervisor status
```

### View live logs
```bash
ddev supervisor tail
```

### Restart everything
```bash
ddev restart
```

### Check DDEV logs
```bash
ddev logs -s web
```

## Production Deployment

For production (non-DDEV), create a supervisor config manually:

1. Install supervisor: `apt-get install supervisor`
2. Create `/etc/supervisor/conf.d/articles-subscribe.conf`:
   ```ini
   [program:articles-subscribe]
   command=php /path/to/artisan articles:subscribe
   autostart=true
   autorestart=true
   user=www-data
   redirect_stderr=true
   stdout_logfile=/path/to/storage/logs/articles-subscribe.log
   ```
3. Reload supervisor: `supervisorctl reread && supervisorctl update`
