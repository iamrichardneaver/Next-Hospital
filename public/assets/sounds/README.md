# Notification Sounds for Hospital Workflow System

## Sound Files Needed

This directory should contain the following MP3 audio files for workflow notifications:

### 1. notification-standard.mp3
- **Use:** Routine patient arrivals, normal queue updates
- **Volume:** Medium
- **Duration:** 1-2 seconds
- **Tone:** Pleasant, attention-grabbing but not alarming

### 2. notification-urgent.mp3
- **Use:** Urgent priority patients, important workflow updates
- **Volume:** Loud
- **Duration:** 1-2 seconds
- **Tone:** More prominent, conveys urgency

### 3. notification-critical.mp3
- **Use:** Critical/emergency patients, emergency alerts
- **Volume:** Very Loud
- **Duration:** 2-3 seconds
- **Tone:** Urgent alarm, demands immediate attention

## Where to Get Free Notification Sounds

- **Notification Sounds**: https://notificationsounds.com/
- **Freesound**: https://freesound.org/
- **Mixkit**: https://mixkit.co/free-sound-effects/notification/
- **Zapsplat**: https://www.zapsplat.com/sound-effect-category/notifications/

## Integration

These sounds are played automatically by the `WorkflowNotificationService` when:
- New patients arrive in staff's queue
- Queue status changes requiring attention
- Priority patients need immediate care
- Emergency alerts are triggered

## Testing

To test sounds, open browser console and run:
```javascript
const audio = new Audio('/assets/sounds/notification-standard.mp3')
audio.play()
```

