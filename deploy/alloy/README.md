# Ship StreetCode logs to North Cloud Loki

StreetCode runs on the same server as North Cloud but not in Docker. To make the North Cloud pipeline dashboard StreetCode panels show data, run Alloy on this host to tail Laravel log files and push to Loki.

## Prerequisites

- North Cloud Loki reachable from this host. When North Cloud runs on the **same server**, use `http://127.0.0.1:3100` in the config. If Loki is on another host, set that URL in the config.
- Laravel log path: default is `current/storage/logs/*.log` (this deploy has no shared_dirs). If you use shared storage, edit the config to `shared/storage/logs/*.log`.

## 1. Install Alloy on the StreetCode server

As **deployer** on streetcode.net:

```bash
mkdir -p ~/bin
curl -sL "https://github.com/grafana/alloy/releases/latest/download/alloy-linux-amd64.zip" -o /tmp/alloy.zip
unzip -o /tmp/alloy.zip -d /tmp && mv /tmp/alloy-linux-amd64 ~/bin/alloy && chmod +x ~/bin/alloy
```

Ensure `alloy` is on your PATH (e.g. add `~/bin` to PATH in `~/.bashrc`).

## 2. Deploy

The deploy copies `deploy/systemd-user/*.service` (including **streetcode-alloy-loki.service**) to `~/.config/systemd/user/` and enables/restarts it with the other StreetCode services. So a normal deploy will install the unit and start it.

**Before the first deploy that includes this:** install the Alloy binary on the server (step 1). If Alloy is in `~/bin/alloy`, edit the unit after deploy once:

```bash
sed -i 's|/usr/bin/alloy|/home/deployer/bin/alloy|' ~/.config/systemd/user/streetcode-alloy-loki.service
systemctl --user daemon-reload
systemctl --user restart streetcode-alloy-loki.service
```

If you do not want to ship logs to Loki, disable the unit: `systemctl --user disable --now streetcode-alloy-loki.service`.

## 3. Verify

- Alloy logs: `journalctl --user -u streetcode-alloy-loki.service -f`
- In Grafana (North Cloud): Explore → Loki, query `{project="north-cloud", service="streetcode"}`.

## Loki on another host

Edit `deploy/alloy/config.alloy` and set the `loki.write "streetcode"` endpoint URL (e.g. `http://northcloud.biz:3100/loki/api/v1/push`). Ensure port 3100 is reachable from this host.
