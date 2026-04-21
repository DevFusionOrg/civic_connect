from ultralytics import YOLO
from pathlib import Path
import sys
import json


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "Image path argument is required", "detections": []}))
        return

    image_path = sys.argv[1]
    annotated_output = sys.argv[2] if len(sys.argv) > 2 else None

    try:
        model_path = Path(__file__).resolve().parent / "model" / "best.pt"
        model = YOLO(str(model_path))
        results = model(image_path, verbose=False)

        detections = []
        for r in results:
            for box in r.boxes:
                detections.append({
                    "confidence": float(box.conf[0]),
                    "bbox": [float(v) for v in box.xyxy[0].tolist()]
                })

        if annotated_output:
            output_path = Path(annotated_output)
            output_path.parent.mkdir(parents=True, exist_ok=True)
            results[0].save(filename=str(output_path))

        print(json.dumps({
            "success": True,
            "detections": detections,
            "annotated_image": annotated_output,
        }))
    except Exception as exc:
        print(json.dumps({"success": False, "error": str(exc), "detections": []}))


if __name__ == "__main__":
    main()
