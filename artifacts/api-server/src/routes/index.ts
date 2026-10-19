import { Router, type IRouter } from "express";
import healthRouter from "./health";
import gaptrackRouter from "./gaptrack";

const router: IRouter = Router();

router.use(healthRouter);
router.use(gaptrackRouter);

export default router;
