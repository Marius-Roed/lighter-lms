export default class Randflake {
    constructor(machineId = 0) {
        this.machineId = machineId & 0x3ff;
        this.sequence = 0;
        this.lastTimestamp = -1;
    }

    generate() {
        let timestamp = Date.now();

        if (timestamp === this.lastTimestamp) {
            this.sequence = (this.sequence + 1) & 0xfff;
            if (this.sequence === 0) {
                while (timestamp <= this.lastTimestamp) {
                    timestamp = Date.now();
                }
            }
        } else {
            this.sequence = 0;
        }

        this.lastTimestamp = timestamp;

        const id = ((BigInt(timestamp) & 0x1ffffffffffn) << 22n) |
            (BigInt(this.machineId) << 12n) |
            BigInt(this.sequence);

        return id.toString(36);
    }
}
